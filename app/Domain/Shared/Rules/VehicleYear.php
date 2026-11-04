<?php

namespace App\Domain\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Año del vehículo entre 1950 y el año actual + 1.
 */
class VehicleYear implements ValidationRule
{
    public const MIN = 1950;

    public static function max(): int
    {
        return (int) now()->year + 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || (int) $value != $value || (int) $value < self::MIN || (int) $value > self::max()) {
            $fail('validation.custom_rules.vehicle_year')->translate(['min' => self::MIN, 'max' => self::max()]);
        }
    }
}
