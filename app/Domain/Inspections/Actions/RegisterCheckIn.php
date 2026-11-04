<?php

namespace App\Domain\Inspections\Actions;

use App\Domain\Appointments\Actions\ChangeAppointmentStatus;
use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Inspections\Enums\FuelLevel;
use App\Domain\Inspections\Support\VehicleSilhouettes;
use App\Domain\Settings\CheckinSettings;
use App\Models\Appointment;
use App\Models\ChecklistItem;
use App\Models\DamageType;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Registra el check-in de una cita: kilometraje, combustible, objetos, observaciones,
 * marcas de daño (zona + tipo obligatorios) y consentimiento LGPD. Sin firma del cliente.
 * Pasa la cita a "recibida" y genera el acta en PDF.
 */
class RegisterCheckIn
{
    public const PHOTO_DIRECTORY = 'inspections/photos';

    public function __construct(
        private readonly CheckinSettings $settings,
        private readonly ChangeAppointmentStatus $changeStatus,
        private readonly GenerateInspectionReport $generateReport,
    ) {}

    /**
     * @param  array{mileage_km?: mixed, fuel_level?: mixed, items_present?: array<int, mixed>, observations?: ?string, lgpd_consent?: mixed, marks?: array<array-key, array<string, mixed>>}  $data
     */
    public function execute(Appointment $appointment, array $data, ?User $inspector = null): Inspection
    {
        $marks = array_values($data['marks'] ?? []);
        if (($data['fuel_level'] ?? null) instanceof FuelLevel) {
            $data['fuel_level'] = $data['fuel_level']->value;
        }
        $this->validate($appointment, $data, $marks);

        $inspection = DB::transaction(function () use ($appointment, $data, $marks, $inspector): Inspection {
            $appointment->refresh();
            if ($appointment->inspection()->exists()) {
                throw ValidationException::withMessages(['appointment' => __('inspections.errors.already_checked_in')]);
            }

            $inspection = $appointment->inspection()->create([
                'vehicle_id' => $appointment->vehicle_id,
                'mileage_km' => isset($data['mileage_km']) && $data['mileage_km'] !== '' ? (int) $data['mileage_km'] : null,
                'fuel_level' => FuelLevel::from((string) $data['fuel_level']),
                'items_present' => array_values(array_map('intval', $data['items_present'] ?? [])),
                'observations' => $data['observations'] ?? null,
                'silhouette' => VehicleSilhouettes::DEFAULT,
                'lgpd_consent_at' => now(),
                'inspected_by' => $inspector?->id,
            ]);

            foreach ($marks as $mark) {
                $zone = (string) $mark['zone'];
                [$cx, $cy] = VehicleSilhouettes::centerOf($zone);

                $inspection->marks()->create([
                    'zone' => $zone,
                    'x' => isset($mark['x']) && $mark['x'] !== '' ? (float) $mark['x'] : $cx,
                    'y' => isset($mark['y']) && $mark['y'] !== '' ? (float) $mark['y'] : $cy,
                    'damage_type_id' => (int) $mark['damage_type_id'],
                    'note' => $mark['note'] ?? null,
                    'photos' => array_values($mark['photos'] ?? []),
                ]);
            }

            if ($inspection->mileage_km !== null) {
                $appointment->vehicle->update(['mileage_km' => $inspection->mileage_km]);
            }
            $this->changeStatus->execute($appointment, AppointmentStatus::CheckedIn);

            return $inspection;
        });

        return $this->generateReport->execute($inspection);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $marks
     */
    private function validate(Appointment $appointment, array $data, array $marks): void
    {
        $errors = [];

        if (! $appointment->status->canTransitionTo(AppointmentStatus::CheckedIn)) {
            $errors['appointment'][] = __('inspections.errors.invalid_status', ['status' => $appointment->status->getLabel()]);
        }

        $mileage = $data['mileage_km'] ?? null;
        if ($this->settings->mileage_required && ($mileage === null || $mileage === '')) {
            $errors['mileage_km'][] = __('inspections.errors.mileage_required');
        } elseif ($mileage !== null && $mileage !== '' && (! is_numeric($mileage) || (int) $mileage < 0 || (int) $mileage != $mileage)) {
            $errors['mileage_km'][] = __('inspections.errors.mileage_invalid');
        }

        if (FuelLevel::tryFrom((string) ($data['fuel_level'] ?? '')) === null) {
            $errors['fuel_level'][] = __('inspections.errors.fuel_required');
        }

        if (! filter_var($data['lgpd_consent'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $errors['lgpd_consent'][] = __('inspections.errors.consent_required');
        }

        $itemIds = array_map('intval', $data['items_present'] ?? []);
        if ($itemIds !== [] && ChecklistItem::query()->whereIn('id', $itemIds)->count() !== count(array_unique($itemIds))) {
            $errors['items_present'][] = __('validation.exists', ['attribute' => __('inspections.fields.items_present')]);
        }

        if (count($marks) > $this->settings->max_marks) {
            $errors['marks'][] = __('inspections.errors.too_many_marks', ['max' => $this->settings->max_marks]);
        }

        $activeTypes = DamageType::query()->where('is_active', true)->pluck('id')->all();
        foreach ($marks as $i => $mark) {
            $n = $i + 1;
            if (! is_string($mark['zone'] ?? null) || ! VehicleSilhouettes::exists($mark['zone'])) {
                $errors["marks.{$i}.zone"][] = __('inspections.errors.mark_zone', ['n' => $n]);
            }
            if (! in_array((int) ($mark['damage_type_id'] ?? 0), $activeTypes, true)) {
                $errors["marks.{$i}.damage_type_id"][] = __('inspections.errors.mark_type', ['n' => $n]);
            }
            foreach (['x', 'y'] as $axis) {
                $v = $mark[$axis] ?? null;
                if ($v !== null && $v !== '' && (! is_numeric($v) || $v < 0 || $v > 100)) {
                    $errors["marks.{$i}.zone"][] = __('inspections.errors.mark_position', ['n' => $n]);
                }
            }

            $photos = array_values($mark['photos'] ?? []);
            if (count($photos) > $this->settings->max_photos_per_mark) {
                $errors["marks.{$i}.photos"][] = __('inspections.errors.too_many_photos', ['max' => $this->settings->max_photos_per_mark]);
            }
            foreach ($photos as $path) {
                if (! is_string($path) || ! str_starts_with($path, self::PHOTO_DIRECTORY.'/') || str_contains($path, '..')
                    || ! Storage::disk((string) config('olympo.private_disk'))->exists($path)) {
                    $errors["marks.{$i}.photos"][] = __('inspections.errors.photo_invalid');
                    break;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
