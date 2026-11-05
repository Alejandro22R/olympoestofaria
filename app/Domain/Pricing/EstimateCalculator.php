<?php

namespace App\Domain\Pricing;

use App\Domain\Catalog\Enums\PricingUnit;
use App\Domain\Settings\PricingSettings;
use App\Models\Material;
use App\Models\ServiceType;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Calcula el rango estimado del constructor a partir de los servicios (service_types)
 * y los materiales elegidos. Margen y redondeo vienen de Ajustes > Cotizaciones y precios.
 *
 * Reglas (todo en centavos enteros):
 * - Mano de obra = precio base × cantidad (si la unidad es m², la cantidad son los m² del servicio).
 * - Material = m² del servicio × cantidad × precio por m² del material (redondeo al centavo más cercano).
 * - Rango = subtotal × (1 ∓ margen %), mínimo redondeado hacia abajo y máximo hacia arriba al múltiplo configurado.
 */
class EstimateCalculator
{
    public function __construct(private readonly PricingSettings $settings) {}

    /**
     * @param  list<array{service_type_id: int, material_id?: ?int, quantity?: int}>  $pieces
     */
    public function calculate(array $pieces): Estimate
    {
        if ($pieces === []) {
            return Estimate::empty();
        }

        /** @var Collection<int, ServiceType> $services */
        $services = ServiceType::query()->whereIn('id', array_column($pieces, 'service_type_id'))->where('is_active', true)->get()->keyBy('id');
        $materialIds = array_filter(array_map(fn (array $p) => $p['material_id'] ?? null, $pieces));
        /** @var Collection<int, Material> $materials */
        $materials = Material::query()->whereIn('id', $materialIds)->where('is_active', true)->get()->keyBy('id');

        $lines = [];
        foreach ($pieces as $piece) {
            $service = $services->get($piece['service_type_id']);
            if ($service === null) {
                throw new InvalidArgumentException("Unknown or inactive service type {$piece['service_type_id']}");
            }

            $quantity = max(1, (int) ($piece['quantity'] ?? 1));
            $material = isset($piece['material_id']) ? $materials->get($piece['material_id']) : null;

            $lines[] = self::line(
                serviceTypeId: $service->id,
                basePriceCents: $service->base_price_cents,
                unit: $service->pricing_unit,
                serviceM2: $service->material_m2 !== null ? (float) $service->material_m2 : null,
                quantity: $quantity,
                materialPricePerM2Cents: $material?->price_per_m2_cents,
            );
        }

        return self::range($lines, $this->settings->estimate_margin_percent, $this->settings->rounding_cents);
    }

    /**
     * Línea pura (sin base de datos) para poder probar cada regla.
     */
    public static function line(int $serviceTypeId, int $basePriceCents, PricingUnit $unit, ?float $serviceM2, int $quantity, ?int $materialPricePerM2Cents): EstimateLine
    {
        if ($basePriceCents < 0 || $quantity < 1 || ($materialPricePerM2Cents !== null && $materialPricePerM2Cents < 0) || ($serviceM2 !== null && $serviceM2 < 0)) {
            throw new InvalidArgumentException('Invalid estimate input');
        }

        $m2 = ($serviceM2 ?? 0.0) * $quantity;

        $laborUnits = $unit === PricingUnit::SquareMeter ? $m2 : $quantity;
        $labor = (int) round($basePriceCents * $laborUnits);

        $material = $materialPricePerM2Cents !== null ? (int) round($m2 * $materialPricePerM2Cents) : 0;

        return new EstimateLine($serviceTypeId, $labor, $material, $m2);
    }

    /**
     * @param  list<EstimateLine>  $lines
     */
    public static function range(array $lines, int $marginPercent, int $roundingCents): Estimate
    {
        if ($marginPercent < 0 || $marginPercent > 100 || $roundingCents < 1) {
            throw new InvalidArgumentException('Invalid estimate settings');
        }

        $subtotal = array_sum(array_map(fn (EstimateLine $l) => $l->totalCents(), $lines));
        if ($subtotal === 0) {
            return new Estimate(0, 0, 0, $lines);
        }

        $min = intdiv($subtotal * (100 - $marginPercent), 100);
        $max = intdiv($subtotal * (100 + $marginPercent) + 99, 100); // techo

        $min = intdiv($min, $roundingCents) * $roundingCents;
        $max = intdiv($max + $roundingCents - 1, $roundingCents) * $roundingCents;

        return new Estimate($subtotal, $min, max($min, $max), $lines);
    }
}
