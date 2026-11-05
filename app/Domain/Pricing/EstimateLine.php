<?php

namespace App\Domain\Pricing;

/**
 * Línea del estimado: mano de obra del servicio + material (m² × precio por m²). Todo en centavos.
 */
final readonly class EstimateLine
{
    public function __construct(
        public int $serviceTypeId,
        public int $laborCents,
        public int $materialCents,
        public float $materialM2,
    ) {}

    public function totalCents(): int
    {
        return $this->laborCents + $this->materialCents;
    }
}
