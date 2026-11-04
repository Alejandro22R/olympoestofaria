<?php

namespace App\Domain\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Teléfono en formato internacional E.164 (+5511987654321).
 */
class E164Phone implements ValidationRule
{
    public static function normalize(string $phone): string
    {
        return '+'.preg_replace('/\D/', '', $phone);
    }

    public static function passes(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^\+[1-9]\d{7,14}$/', $value);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::passes($value)) {
            $fail('validation.custom_rules.phone_e164')->translate();
        }
    }
}
