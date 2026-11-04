<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Taller > Check-in y checklists.
 */
class CheckinSettings extends Settings
{
    public bool $mileage_required;

    public int $max_photos_per_mark;

    public int $max_marks;

    /** Días de validez del enlace del acta enviado al cliente. */
    public int $report_link_days;

    /** @var array<string, string> Texto legal del acta por idioma. */
    public array $legal_text;

    /** @var array<string, string> Texto del consentimiento LGPD por idioma. */
    public array $consent_text;

    public static function group(): string
    {
        return 'checkin';
    }

    public function legalText(?string $locale = null): string
    {
        return $this->legal_text[$locale ?? app()->getLocale()] ?? $this->legal_text['pt_BR'] ?? '';
    }

    public function consentText(?string $locale = null): string
    {
        return $this->consent_text[$locale ?? app()->getLocale()] ?? $this->consent_text['pt_BR'] ?? '';
    }
}
