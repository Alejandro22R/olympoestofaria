<?php

namespace App\Domain\Pricing;

/**
 * Rango estimado (mínimo–máximo) que ve el cliente; el taller confirma el precio final.
 */
final readonly class Estimate
{
    /**
     * @param  list<EstimateLine>  $lines
     */
    public function __construct(
        public int $subtotalCents,
        public int $minCents,
        public int $maxCents,
        public array $lines,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, []);
    }

    public function isEmpty(): bool
    {
        return $this->subtotalCents === 0;
    }
}
