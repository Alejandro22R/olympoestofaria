<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Taller > General.
 */
class GeneralSettings extends Settings
{
    public string $business_name;

    public string $city;

    public ?string $whatsapp;

    public ?string $email;

    public ?string $maps_url;

    public string $default_locale;

    /** @var list<string> */
    public array $portal_locales;

    public string $timezone;

    public string $currency;

    /** País usado para validar placa y documento (ISO 3166-1 alfa-2). */
    public string $country;

    public static function group(): string
    {
        return 'general';
    }
}
