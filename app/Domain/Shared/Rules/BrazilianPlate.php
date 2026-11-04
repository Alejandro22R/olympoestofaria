<?php

namespace App\Domain\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Placa brasileña: formato antiguo (ABC-1234 / ABC1234) o Mercosul (ABC1D23).
 */
class BrazilianPlate implements ValidationRule
{
    public const OLD = '/^[A-Z]{3}-?\d{4}$/';

    public const MERCOSUL = '/^[A-Z]{3}\d[A-Z]\d{2}$/';

    public static function normalize(string $plate): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $plate));
    }

    public static function passes(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = strtoupper(trim($value));

        return (bool) preg_match(self::OLD, $value)
            || (bool) preg_match(self::MERCOSUL, str_replace('-', '', $value));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::passes($value)) {
            $fail('validation.custom_rules.plate')->translate();
        }
    }
}
