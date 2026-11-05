<?php

namespace App\Domain\Projects;

use App\Domain\Settings\GeneralSettings;
use App\Filament\Support\Money;
use App\Models\MaterialVariant;
use App\Models\Project;
use App\Models\ServiceType;

/**
 * Resumen legible del proyecto y enlace de WhatsApp al taller (MVP con wa.me).
 */
class ProjectSummary
{
    public function __construct(private readonly GeneralSettings $general) {}

    /**
     * @return list<array{service: string, material: ?string, color: ?string}>
     */
    public function lines(Project $project): array
    {
        $pieces = $project->configuration['pieces'] ?? [];
        $services = ServiceType::query()->whereIn('id', array_column($pieces, 'service_type_id'))->get()->keyBy('id');
        $variants = MaterialVariant::query()->with('material')->whereIn('id', array_filter(array_column($pieces, 'material_variant_id')))->get()->keyBy('id');

        return array_map(function (array $p) use ($services, $variants) {
            $variant = isset($p['material_variant_id']) ? $variants->get($p['material_variant_id']) : null;

            return [
                'service' => (string) $services->get($p['service_type_id'])?->name,
                'material' => $variant?->material->name,
                'color' => $variant?->name,
            ];
        }, $pieces);
    }

    public function text(Project $project): string
    {
        $lines = array_map(
            fn (array $l) => '• '.$l['service'].($l['material'] ? ' — '.$l['material'].' ('.$l['color'].')' : ''),
            $this->lines($project),
        );

        return __('projects.whatsapp.message', [
            'number' => $project->number(),
            'vehicle' => $project->vehicleLabel(),
            'pieces' => implode("\n", $lines),
            'min' => Money::format($project->estimate_min_cents),
            'max' => Money::format($project->estimate_max_cents),
        ]);
    }

    public function whatsappUrl(Project $project): ?string
    {
        $phone = $this->general->whatsapp;
        if (blank($phone)) {
            return null;
        }

        return 'https://wa.me/'.ltrim((string) $phone, '+').'?text='.rawurlencode($this->text($project));
    }
}
