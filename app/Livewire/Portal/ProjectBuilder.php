<?php

namespace App\Livewire\Portal;

use App\Domain\Pricing\Estimate;
use App\Domain\Pricing\EstimateCalculator;
use App\Domain\Projects\Actions\SubmitProject;
use App\Domain\Projects\ProjectRules;
use App\Domain\Settings\PricingSettings;
use App\Models\Material;
use App\Models\MaterialVariant;
use App\Models\ServiceType;
use App\Models\VehicleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * Constructor de proyectos del portal: vehículo → piezas → material y color → resumen y contacto.
 * El borrador se guarda en la sesión para no perder el avance; el estimado se recalcula en el servidor.
 *
 * @property-read Collection<int, ServiceType> $services
 * @property-read Collection<int, Material> $materials
 * @property-read Collection<int, VehicleModel> $vehicleModels
 * @property-read Estimate $estimate
 */
#[Layout('layouts.portal')]
class ProjectBuilder extends Component
{
    public const STEPS = 4;

    public const MAX_SUBMITS_PER_HOUR = 5;

    #[Session(key: 'builder.step')]
    public int $step = 1;

    /** @var array{vehicle_model_id: ?int, brand: ?string, model: ?string, year: int|string|null} */
    #[Session(key: 'builder.vehicle')]
    public array $vehicle = ['vehicle_model_id' => null, 'brand' => null, 'model' => null, 'year' => null];

    /** @var array<int, ?int> service_type_id => material_variant_id */
    #[Session(key: 'builder.selection')]
    public array $selection = [];

    #[Session(key: 'builder.stitching')]
    public ?string $stitching = '#C9C9C9';

    #[Session(key: 'builder.notes')]
    public ?string $notes = null;

    /** @var array{name: ?string, phone: ?string, email: ?string, locale: string} */
    #[Session(key: 'builder.contact')]
    public array $contact = ['name' => null, 'phone' => null, 'email' => null, 'locale' => 'pt_BR'];

    public bool $privacy = false;

    public bool $contact_opt_in = true;

    /** @var array<string, string> Colores del visualizador (zona => hex), leídos por Alpine vía.colors. */
    public array $colors = [];

    /** Honeypot contra spam: los humanos no lo ven ni lo llenan. */
    public string $website = '';

    public function mount(): void
    {
        $this->contact['locale'] = app()->getLocale();
        $preselect = request()->query('servico');
        if (is_string($preselect) && ($service = ServiceType::query()->where('slug', $preselect)->where('is_active', true)->first())) {
            $this->selection[$service->id] ??= null;
        }
        $this->syncColors();
    }

    public function updatedStitching(): void
    {
        $this->syncColors();
    }

    public function selectModel(?int $id): void
    {
        $model = $id ? VehicleModel::query()->where('is_active', true)->find($id) : null;
        $this->vehicle['vehicle_model_id'] = $model?->id;
        if ($model) {
            $this->vehicle['brand'] = $model->brand;
            $this->vehicle['model'] = $model->model;
        }
    }

    public function toggleService(int $serviceId): void
    {
        if (array_key_exists($serviceId, $this->selection)) {
            unset($this->selection[$serviceId]);
        } elseif ($this->services->has($serviceId)) {
            $this->selection[$serviceId] = null;
        }
        $this->syncColors();
    }

    public function selectVariant(int $serviceId, int $variantId): void
    {
        if (array_key_exists($serviceId, $this->selection) && MaterialVariant::query()->where('is_active', true)->whereKey($variantId)->exists()) {
            $this->selection[$serviceId] = $variantId;
        }
        $this->syncColors();
    }

    public function next(): void
    {
        $this->validateStep($this->step);
        $this->step = min(self::STEPS, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function goTo(int $step): void
    {
        for ($s = 1; $s < min($step, self::STEPS); $s++) {
            $this->validateStep($s);
        }
        $this->step = max(1, min($step, self::STEPS));
    }

    public function submit(): void
    {
        if ($this->website !== '') {
            // Bot: se responde como si todo saliera bien, sin guardar nada.
            $this->redirectRoute('home');

            return;
        }

        $key = 'builder-submit:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_SUBMITS_PER_HOUR)) {
            throw ValidationException::withMessages(['contact.phone' => __('portal.builder.errors.too_many', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)])]);
        }

        foreach ([1, 2, 3] as $s) {
            $this->validateStep($s);
        }

        $this->validate([
            ...ProjectRules::contact(),
            'contact.phone' => [...ProjectRules::contact()['contact.phone'], function (string $attribute, mixed $value, \Closure $fail): void {
                if (! ProjectRules::phoneIsValid((string) $value)) {
                    $fail('validation.custom_rules.phone_e164')->translate();
                }
            }],
        ]);

        RateLimiter::hit($key, 3600);

        $project = app(SubmitProject::class)->execute([
            'vehicle' => $this->vehicle,
            'pieces' => $this->piecesPayload(),
            'stitching' => $this->stitching,
            'notes' => $this->notes,
            'contact' => $this->contact,
            'privacy' => $this->privacy,
            'contact_opt_in' => $this->contact_opt_in,
        ]);

        $this->resetDraft();

        $this->redirect(URL::temporarySignedRoute('portal.project.sent', now()->addDays(7), ['project' => $project->id]));
    }

    private function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate(ProjectRules::vehicle()),
            2 => $this->selection === []
                ? throw ValidationException::withMessages(['selection' => __('portal.builder.errors.no_pieces')])
                : null,
            3 => $this->validateMaterials(),
            default => null,
        };
    }

    private function validateMaterials(): void
    {
        $errors = [];
        foreach ($this->selection as $serviceId => $variantId) {
            $service = $this->services->get($serviceId);
            if ($service !== null && $service->material_m2 !== null && $variantId === null) {
                $errors['selection.'.$serviceId] = __('projects.errors.material_required', ['service' => $service->name]);
            }
        }
        $this->validate(['stitching' => ProjectRules::pieces()['stitching'], 'notes' => ProjectRules::pieces()['notes']]);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return list<array{service_type_id: int, material_variant_id: ?int}>
     */
    private function piecesPayload(): array
    {
        $pieces = [];
        foreach ($this->selection as $serviceId => $variantId) {
            $pieces[] = ['service_type_id' => (int) $serviceId, 'material_variant_id' => $variantId];
        }

        return $pieces;
    }

    private function resetDraft(): void
    {
        $this->reset(['step', 'vehicle', 'selection', 'stitching', 'notes', 'privacy']);
        foreach (['step', 'vehicle', 'selection', 'stitching', 'notes', 'contact'] as $key) {
            session()->forget('builder.'.$key);
        }
    }

    /**
     * @return Collection<int, ServiceType>
     */
    #[Computed]
    public function services(): Collection
    {
        return ServiceType::query()->where('is_active', true)->orderBy('sort')->get()->keyBy('id');
    }

    /**
     * @return Collection<int, Material>
     */
    #[Computed]
    public function materials(): Collection
    {
        return Material::query()->where('is_active', true)
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('type')->get();
    }

    /**
     * @return Collection<int, VehicleModel>
     */
    #[Computed]
    public function vehicleModels(): Collection
    {
        return VehicleModel::query()->where('is_active', true)->orderBy('brand')->orderBy('model')->get();
    }

    #[Computed]
    public function estimate(): Estimate
    {
        if ($this->selection === []) {
            return Estimate::empty();
        }

        $variants = MaterialVariant::query()->whereIn('id', array_filter($this->selection))->pluck('material_id', 'id');

        return app(EstimateCalculator::class)->calculate(array_map(fn (array $p) => [
            'service_type_id' => $p['service_type_id'],
            'material_id' => $p['material_variant_id'] ? ($variants[$p['material_variant_id']] ?? null) : null,
        ], $this->piecesPayload()));
    }

    /**
     * Colores para el visualizador: zona (slug del servicio) => color de la variante elegida.
     */
    private function syncColors(): void
    {
        $this->colors = $this->previewColors();
    }

    /**
     * @return array<string, string>
     */
    private function previewColors(): array
    {
        $variants = MaterialVariant::query()->whereIn('id', array_filter($this->selection))->pluck('color_hex', 'id');
        $colors = [
            'seats' => '#2A2A2A', 'steering_wheel' => '#1F1F1F', 'doors' => '#262626',
            'headliner' => '#3A3A3A', 'flooring' => '#141414', 'stitching' => $this->stitching ?? '#C9C9C9',
        ];
        foreach ($this->selection as $serviceId => $variantId) {
            $slug = $this->services->get($serviceId)?->slug;
            if ($slug !== null && $variantId !== null && isset($colors[$slug])) {
                $colors[$slug] = (string) $variants[$variantId];
            }
        }

        return $colors;
    }

    public function render(): View
    {
        return view('livewire.portal.project-builder', [
            'showEstimate' => app(PricingSettings::class)->show_estimate,
            'stitchingColors' => ['#C9C9C9', '#FFFFFF', '#0B8FD9', '#D64545', '#E0A100', '#111111'],
        ])->title(__('portal.builder.title').' · Olympo');
    }
}
