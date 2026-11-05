<?php

namespace App\Domain\Quotes\Actions;

use App\Domain\Quotes\QuoteTotals;
use App\Domain\Settings\PaymentSettings;
use App\Domain\Settings\PricingSettings;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Guarda una cotización en borrador: ítems, descuento, vigencia y notas.
 * Solo los roles de Ajustes > Cotizaciones pueden fijar el precio final; el descuento
 * no supera el máximo configurado; los totales se recalculan siempre en el servidor.
 */
class SaveQuote
{
    public function __construct(
        private readonly PricingSettings $pricing,
        private readonly PaymentSettings $payments,
    ) {}

    /**
     * @param  array{items?: array<array-key, array<string, mixed>>, discount_cents?: mixed, valid_until?: mixed, notes?: ?string, vehicle_id?: ?int, appointment_id?: ?int}  $data
     */
    public function execute(Quote $quote, array $data, ?User $user = null): Quote
    {
        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages(['items' => __('quotes.errors.not_editable')]);
        }

        $items = array_values($data['items'] ?? $quote->items->map->only(['service_type_id', 'material_variant_id', 'description', 'quantity', 'unit_price_cents'])->all());

        if ($user !== null && ! $this->canSetPrices($user)) {
            $this->assertPricesUnchanged($quote, $items);
        }

        $lines = [];
        foreach ($items as $i => $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unit = (int) ($item['unit_price_cents'] ?? 0);
            if (blank($item['description'] ?? null)) {
                throw ValidationException::withMessages(["items.{$i}.description" => __('validation.required', ['attribute' => __('quotes.fields.description')])]);
            }
            try {
                $total = QuoteTotals::itemTotal($quantity, $unit);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages(["items.{$i}.quantity" => __('quotes.errors.invalid_item')]);
            }
            $lines[] = [
                'service_type_id' => $item['service_type_id'] ?? null,
                'material_variant_id' => $item['material_variant_id'] ?? null,
                'description' => mb_substr((string) $item['description'], 0, 255),
                'quantity' => $quantity,
                'unit_price_cents' => $unit,
                'total_cents' => $total,
                'sort' => $i,
            ];
        }

        $discount = (int) ($data['discount_cents'] ?? $quote->discount_cents);
        try {
            $totals = QuoteTotals::compute(array_column($lines, 'total_cents'), $discount, $this->pricing->max_discount_percent, $this->payments->deposit_percent);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['discount_cents' => __('quotes.errors.discount', ['max' => $this->pricing->max_discount_percent])]);
        }

        $validUntil = $data['valid_until'] ?? $quote->valid_until ?? now()->addDays($this->pricing->quote_validity_days)->toDateString();

        return DB::transaction(function () use ($quote, $data, $lines, $totals, $validUntil): Quote {
            $quote->fill([
                'subtotal_cents' => $totals['subtotal'],
                'discount_cents' => $totals['discount'],
                'total_cents' => $totals['total'],
                'deposit_cents' => $totals['deposit'],
                'valid_until' => $validUntil,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $quote->notes,
            ]);
            if (array_key_exists('vehicle_id', $data)) {
                $quote->vehicle_id = $data['vehicle_id'];
                if ($data['vehicle_id'] !== null) {
                    $quote->vehicle_label = Vehicle::query()->find($data['vehicle_id'])?->label();
                }
            }
            $quote->save();

            $quote->items()->delete();
            $quote->items()->createMany($lines);

            return $quote->load('items');
        });
    }

    public function canSetPrices(User $user): bool
    {
        return $user->hasAnyRole($this->pricing->final_price_roles);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function assertPricesUnchanged(Quote $quote, array $items): void
    {
        $current = $quote->items->pluck('unit_price_cents')->values()->all();
        $incoming = array_map(fn ($i) => (int) ($i['unit_price_cents'] ?? 0), $items);

        if ($current !== $incoming) {
            throw ValidationException::withMessages(['items' => __('quotes.errors.price_role')]);
        }
    }
}
