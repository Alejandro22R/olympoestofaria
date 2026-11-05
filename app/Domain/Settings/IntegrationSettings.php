<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Integraciones opcionales sin sección propia (Cloudflare Turnstile contra spam).
 */
class IntegrationSettings extends Settings
{
    public bool $turnstile_enabled;

    public ?string $turnstile_site_key;

    public ?string $turnstile_secret;

    public static function group(): string
    {
        return 'integrations';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['turnstile_secret'];
    }

    public function turnstileReady(): bool
    {
        return $this->turnstile_enabled && filled($this->turnstile_site_key) && filled($this->turnstile_secret);
    }
}
