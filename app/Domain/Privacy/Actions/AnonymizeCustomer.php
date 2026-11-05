<?php

namespace App\Domain\Privacy\Actions;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Inspection;
use App\Models\InspectionMark;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\ReportSend;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/**
 * Anonimización LGPD (art. 18, IV y VI): el cliente deja de ser identificable, pero se conservan
 * los montos, servicios y fechas que la ley fiscal obliga a guardar. Irreversible.
 *
 * - Datos de contacto, documento, notas y cuenta del portal: se borran.
 * - Placas: se reemplazan por un código interno.
 * - PDF de actas y cotizaciones y fotos del vehículo: se borran (muestran nombre y placa).
 * - Destinatarios en registros de envío y valores antiguos en la auditoría: se borran.
 */
class AnonymizeCustomer
{
    public function execute(Customer $customer, ?User $by = null): Customer
    {
        if ($customer->isAnonymized()) {
            throw ValidationException::withMessages(['customer' => __('privacy.errors.already_anonymized')]);
        }

        $vehicleIds = Vehicle::withTrashed()->where('customer_id', $customer->id)->pluck('id');
        $this->ensureNothingOpen($customer, $vehicleIds->all());

        $files = [];

        DB::transaction(function () use ($customer, $vehicleIds, $by, &$files): void {
            $orderIds = WorkOrder::query()->whereIn('vehicle_id', $vehicleIds)->pluck('id');
            $inspections = Inspection::query()->whereIn('vehicle_id', $vehicleIds)->get();

            // Archivos que identifican (se borran después del commit).
            foreach ($inspections as $inspection) {
                $files[] = [$inspection->report_disk, $inspection->report_path];
                foreach (InspectionMark::query()->where('inspection_id', $inspection->id)->get() as $mark) {
                    foreach ($mark->photos ?? [] as $path) {
                        $files[] = [config('olympo.private_disk'), $path];
                    }
                    $mark->forceFill(['photos' => [], 'note' => null])->save();
                }
                $inspection->forceFill(['report_path' => null, 'observations' => null])->save();
            }
            foreach (WorkOrderUpdate::query()->whereIn('work_order_id', $orderIds)->get() as $update) {
                foreach ($update->photos ?? [] as $path) {
                    $files[] = [config('olympo.private_disk'), $path];
                }
                $update->forceFill(['photos' => [], 'note' => null])->save();
            }
            foreach (Delivery::query()->whereIn('work_order_id', $orderIds)->get() as $delivery) {
                $files[] = [$delivery->report_disk, $delivery->report_path];
                $delivery->forceFill(['report_path' => null, 'notes' => null])->save();
            }
            foreach (Quote::query()->where('customer_id', $customer->id)->get() as $quote) {
                $files[] = [$quote->report_disk, $quote->report_path];
                $quote->forceFill(['report_path' => null, 'notes' => null])->save();
            }
            if ($document = $customer->document()->first()) {
                $files[] = [$document->disk, $document->path];
                $document->delete();
            }

            WorkOrder::query()->whereIn('id', $orderIds)->update(['notes' => null]);
            Appointment::query()->whereIn('vehicle_id', $vehicleIds)->update(['notes' => null]);
            foreach (Vehicle::withTrashed()->whereIn('id', $vehicleIds)->get() as $vehicle) {
                $vehicle->forceFill(['plate' => 'ANON'.str_pad((string) $vehicle->id, 6, '0', STR_PAD_LEFT), 'notes' => null])->saveQuietly();
            }
            $customer->projects()->update(['notes' => null]);
            Review::query()->where('customer_id', $customer->id)->update(['is_published' => false, 'consent_publish' => false, 'comment' => null]);

            // Registros de envío y auditoría sin datos de contacto.
            NotificationLog::query()->where('notifiable_type', $customer->getMorphClass())->where('notifiable_id', $customer->id)->update(['recipient' => null]);
            $sendables = collect([
                [(new Inspection)->getMorphClass(), $inspections->pluck('id')],
                [(new Delivery)->getMorphClass(), Delivery::query()->whereIn('work_order_id', $orderIds)->pluck('id')],
                [(new Quote)->getMorphClass(), Quote::query()->where('customer_id', $customer->id)->pluck('id')],
            ]);
            foreach ($sendables as [$type, $ids]) {
                ReportSend::query()->where('sendable_type', $type)->whereIn('sendable_id', $ids)->update(['recipient' => '—']);
            }
            Activity::query()->where(function ($q) use ($customer, $vehicleIds): void {
                $q->where(fn ($q) => $q->where('subject_type', $customer->getMorphClass())->where('subject_id', $customer->id))
                    ->orWhere(fn ($q) => $q->where('subject_type', (new Vehicle)->getMorphClass())->whereIn('subject_id', $vehicleIds));
            })->update(['properties' => json_encode([])]);

            $user = $customer->user;
            $customer->forceFill([
                'user_id' => null,
                'name' => __('privacy.anonymous_name', ['id' => $customer->id], 'pt_BR'),
                'email' => null,
                'phone' => '',
                'document_type' => null,
                'document_number' => null,
                'notes' => null,
                'notification_preferences' => null,
                'contact_opt_in_at' => null,
                'unsubscribed_at' => now(),
                'referral_code' => null,
                'anonymized_at' => now(),
            ])->saveQuietly();
            if ($user !== null && ! $user->isStaff()) {
                $user->delete();
            }

            activity('privacy')->causedBy($by)->performedOn($customer)->event('anonymized')->log('privacy.anonymized');
        });

        DB::afterCommit(function () use ($files): void {
            foreach ($files as [$disk, $path]) {
                if ($disk && $path) {
                    Storage::disk($disk)->delete($path);
                }
            }
        });

        return $customer->refresh();
    }

    /**
     * @param  list<int>  $vehicleIds
     */
    private function ensureNothingOpen(Customer $customer, array $vehicleIds): void
    {
        $openOrders = WorkOrder::query()->whereIn('vehicle_id', $vehicleIds)
            ->whereNotIn('status', [WorkOrderStatus::Delivered, WorkOrderStatus::Cancelled])->exists();
        $upcoming = Appointment::query()->whereIn('vehicle_id', $vehicleIds)
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed])->exists();
        $pending = Payment::query()->where('customer_id', $customer->id)->where('status', PaymentStatus::Pending)->exists();

        if ($openOrders || $upcoming || $pending) {
            throw ValidationException::withMessages(['customer' => __('privacy.errors.open_items')]);
        }
    }
}
