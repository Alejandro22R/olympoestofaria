<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Sistema > Privacidad y LGPD.
 */
class PrivacySettings extends Settings
{
    /** Si es falso, no se aceptan fotos de documento (solo el número, cifrado). */
    public bool $store_document_photo;

    /** Anonimizar clientes sin actividad tras N meses (0 = nunca). */
    public int $inactive_customer_months;

    /** Borrar solicitudes perdidas tras N meses (0 = nunca). */
    public int $lost_lead_months;

    /** @var array<string, string> Markdown por idioma */
    public array $privacy_policy;

    /** @var array<string, string> Markdown por idioma */
    public array $terms;

    public ?string $legal_updated_at;

    /** Contacto del encargado de datos (LGPD). */
    public ?string $contact_email;

    public static function group(): string
    {
        return 'privacy';
    }

    /**
     * @param  'privacy_policy'|'terms'  $document
     */
    public function text(string $document, ?string $locale = null): string
    {
        $texts = $this->{$document};
        $locale ??= app()->getLocale();

        return (string) ($texts[$locale] ?? $texts['pt_BR'] ?? '');
    }
}
