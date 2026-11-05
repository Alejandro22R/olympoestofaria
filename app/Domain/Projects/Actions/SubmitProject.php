<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Auth\Permission;
use App\Domain\Notifications\Customer\CustomerEvents;
use App\Domain\Notifications\StaffNotifier;
use App\Domain\Pricing\EstimateCalculator;
use App\Domain\Privacy\ConsentRecorder;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Events\LeadCreated;
use App\Domain\Projects\ProjectRules;
use App\Domain\Shared\Rules\E164Phone;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MaterialVariant;
use App\Models\Project;
use App\Models\ServiceType;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Recibe el proyecto armado en el portal: valida en el servidor, recalcula el estimado
 * (nunca se confía en el del navegador), crea o actualiza el cliente y avisa al taller al instante.
 */
class SubmitProject
{
    public function __construct(
        private readonly EstimateCalculator $calculator,
        private readonly StaffNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Project
    {
        $validated = Validator::make($data, ProjectRules::all())->validate();

        $phone = E164Phone::normalize((string) $validated['contact']['phone']);
        if (! E164Phone::passes($phone)) {
            throw ValidationException::withMessages(['contact.phone' => __('validation.custom_rules.phone_e164')]);
        }

        $pieces = $this->resolvePieces($validated['pieces']);
        $estimate = $this->calculator->calculate(array_map(fn (array $p) => [
            'service_type_id' => $p['service_type_id'],
            'material_id' => $p['material_id'],
        ], $pieces));

        $project = DB::transaction(function () use ($validated, $phone, $pieces, $estimate): Project {
            $customer = Customer::query()->firstOrNew(['phone' => $phone]);
            $customer->name = $customer->exists ? $customer->name : $validated['contact']['name'];
            $customer->email ??= $validated['contact']['email'] ?? null;
            $customer->locale = $validated['contact']['locale'];
            $customer->branch_id ??= Branch::default()?->id;
            if (($validated['contact_opt_in'] ?? false) && $customer->contact_opt_in_at === null) {
                $customer->contact_opt_in_at = now();
            }
            $customer->save();

            // Prueba del consentimiento (privacidad obligatoria; contacto opcional).
            $consents = app(ConsentRecorder::class);
            $consents->record($customer, ConsentRecorder::PRIVACY, true, 'portal');
            if ($customer->lgpd_consent_at === null) {
                $customer->forceFill(['lgpd_consent_at' => now()])->save();
            }
            $consents->record($customer, ConsentRecorder::CONTACT, (bool) ($validated['contact_opt_in'] ?? false), 'portal');

            $referrer = filled($validated['referral'] ?? null)
                ? Customer::query()->where('referral_code', strtoupper((string) $validated['referral']))->whereKeyNot($customer->id)->first()
                : null;
            if ($referrer !== null && $customer->referred_by_customer_id === null && $customer->wasRecentlyCreated) {
                $customer->forceFill(['referred_by_customer_id' => $referrer->id])->save();
            }

            return $customer->projects()->create([
                'branch_id' => $customer->branch_id,
                'referred_by_customer_id' => $referrer?->id,
                'vehicle_model_id' => $validated['vehicle']['vehicle_model_id'] ?? null,
                'status' => ProjectStatus::Submitted,
                'configuration' => [
                    'vehicle' => [
                        'vehicle_model_id' => $validated['vehicle']['vehicle_model_id'] ?? null,
                        'brand' => $validated['vehicle']['brand'],
                        'model' => $validated['vehicle']['model'],
                        'year' => $validated['vehicle']['year'] ?? null,
                    ],
                    'pieces' => $pieces,
                    'stitching' => $validated['stitching'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'contact_name' => $validated['contact']['name'],
                    'privacy_accepted_at' => now()->toIso8601String(),
                ],
                'estimate_min_cents' => $estimate->minCents,
                'estimate_max_cents' => $estimate->maxCents,
                'source' => 'builder',
                'submitted_at' => now(),
            ]);
        });

        LeadCreated::dispatch($project->id);
        app(CustomerEvents::class)->projectReceived($project);
        $this->notifier->notify(Permission::LeadsManage, new StaffAlert(
            type: 'lead_created',
            titleKey: 'notifications.lead_created',
            params: ['vehicle' => $project->vehicleLabel(), 'number' => $project->number()],
            bodyKey: 'notifications.lead_created_body',
            url: ProjectResource::getUrl('view', ['record' => $project]),
            color: 'success',
            icon: 'heroicon-o-sparkles',
        ));

        return $project;
    }

    /**
     * Normaliza las piezas y verifica que el material elegido aplique al servicio.
     *
     * @param  array<int, array<string, mixed>>  $pieces
     * @return list<array{service_type_id: int, material_id: ?int, material_variant_id: ?int, color_hex: ?string}>
     */
    private function resolvePieces(array $pieces): array
    {
        $services = ServiceType::query()->whereIn('id', array_column($pieces, 'service_type_id'))->get()->keyBy('id');
        $variants = MaterialVariant::query()->with('material')
            ->whereIn('id', array_filter(array_column($pieces, 'material_variant_id')))->get()->keyBy('id');

        $result = [];
        foreach (array_values($pieces) as $i => $piece) {
            $service = $services[(int) $piece['service_type_id']];
            $variant = isset($piece['material_variant_id']) ? $variants->get((int) $piece['material_variant_id']) : null;

            if ($variant !== null && ! $variant->material->is_active) {
                throw ValidationException::withMessages(["pieces.{$i}.material_variant_id" => __('projects.errors.material_unavailable')]);
            }
            if ($service->material_m2 !== null && $variant === null) {
                throw ValidationException::withMessages(["pieces.{$i}.material_variant_id" => __('projects.errors.material_required', ['service' => $service->name])]);
            }

            $result[] = [
                'service_type_id' => $service->id,
                'material_id' => $variant?->material_id,
                'material_variant_id' => $variant?->id,
                'color_hex' => $variant?->color_hex,
            ];
        }

        return $result;
    }
}
