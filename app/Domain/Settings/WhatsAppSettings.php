<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Sistema > Integraciones > WhatsApp Cloud API (Meta). Token cifrado y de solo escritura.
 */
class WhatsAppSettings extends Settings
{
    public bool $enabled;

    public ?string $phone_number_id;

    public ?string $business_account_id;

    public ?string $access_token;

    public string $api_version;

    public static function group(): string
    {
        return 'whatsapp';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['access_token'];
    }

    public function isReady(): bool
    {
        return $this->enabled && filled($this->phone_number_id) && filled($this->access_token);
    }
}
