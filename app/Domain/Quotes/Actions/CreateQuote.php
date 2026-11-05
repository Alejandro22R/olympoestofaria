<?php

namespace App\Domain\Quotes\Actions;

use App\Domain\Pricing\EstimateCalculator;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\ProjectSummary;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Settings\PricingSettings;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Abre una cotización en borrador, a partir de un lead (con los precios del estimado como punto de partida)
 * o vacía para un cliente.
 */
class CreateQuote
{
    public function __construct(
        private readonly SaveQuote $save,
        private readonly EstimateCalculator $calculator,
        private readonly ProjectSummary $summary,
        private readonly PricingSettings $pricing,
    ) {}

    public function fromProject(Project $project, ?User $user = null): Quote
    {
        if (in_array($project->status, [ProjectStatus::Approved, ProjectStatus::Lost], true)) {
            throw ValidationException::withMessages(['project' => __('quotes.errors.project_closed')]);
        }
        if ($this->pricing->require_contact_before_quote && $project->status === ProjectStatus::Submitted) {
            throw ValidationException::withMessages(['project' => __('quotes.errors.contact_first')]);
        }

        $quote = $this->blank($project->customer, $user, [
            'project_id' => $project->id,
            'vehicle_label' => $project->vehicleLabel(),
        ]);

        $pieces = $project->configuration['pieces'] ?? [];
        $estimate = $this->calculator->calculate(array_map(fn (array $p) => [
            'service_type_id' => (int) $p['service_type_id'],
            'material_id' => $p['material_id'] ?? null,
        ], $pieces));
        $labels = $this->summary->lines($project);

        $items = [];
        foreach ($estimate->lines as $i => $line) {
            $label = $labels[$i] ?? ['service' => '', 'material' => null, 'color' => null];
            $items[] = [
                'service_type_id' => $line->serviceTypeId,
                'material_variant_id' => $pieces[$i]['material_variant_id'] ?? null,
                'description' => trim($label['service'].($label['material'] ? ' — '.$label['material'].' ('.$label['color'].')' : '')),
                'quantity' => 1,
                'unit_price_cents' => $line->totalCents(),
            ];
        }

        // Precio inicial = estimado (sin el margen); el taller lo ajusta antes de enviar.
        return $this->save->execute($quote, ['items' => $items]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function blank(Customer $customer, ?User $user = null, array $attributes = []): Quote
    {
        return Quote::query()->create([
            'branch_id' => $customer->branch_id ?? Branch::default()?->id,
            'customer_id' => $customer->id,
            'status' => QuoteStatus::Draft,
            'version' => 1,
            'valid_until' => now()->addDays($this->pricing->quote_validity_days)->toDateString(),
            'created_by' => $user?->id,
            ...$attributes,
        ]);
    }
}
