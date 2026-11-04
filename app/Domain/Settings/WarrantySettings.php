<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Taller > Garantías. El plazo por servicio vive en la tabla warranty_terms.
 */
class WarrantySettings extends Settings
{
    /** Plazo cuando ningún servicio de la orden tiene uno propio. */
    public int $default_months;

    /** Días antes del vencimiento para recordar la revisión (Fase 6). */
    public int $review_reminder_days;

    /** @var array<string, string> */
    public array $conditions;

    public static function group(): string
    {
        return 'warranty';
    }

    public function conditionsText(?string $locale = null): string
    {
        return $this->conditions[$locale ?? app()->getLocale()] ?? $this->conditions['pt_BR'] ?? '';
    }
}
