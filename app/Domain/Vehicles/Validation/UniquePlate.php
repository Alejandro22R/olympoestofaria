<?php

namespace App\Domain\Vehicles\Validation;

use App\Domain\Shared\Rules\BrazilianPlate;
use App\Models\Vehicle;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Placa única comparando la versión normalizada (ABC-1234 == ABC1234), incluidos vehículos archivados.
 */
class UniquePlate implements ValidationRule
{
    public function __construct(private readonly ?Vehicle $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $exists = Vehicle::withTrashed()
            ->where('plate', BrazilianPlate::normalize($value))
            ->when($this->ignore?->exists, fn ($q) => $q->whereKeyNot($this->ignore?->getKey()))
            ->exists();

        if ($exists) {
            $fail('validation.unique')->translate();
        }
    }
}
