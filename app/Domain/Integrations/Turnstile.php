<?php

namespace App\Domain\Integrations;

use App\Domain\Settings\IntegrationSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Verificación de Cloudflare Turnstile (anti-spam del constructor). Si está apagado o sin llaves,
 * no bloquea: el honeypot y el límite por IP siguen activos.
 */
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(private readonly IntegrationSettings $settings) {}

    public function enabled(): bool
    {
        return $this->settings->turnstileReady();
    }

    public function siteKey(): ?string
    {
        return $this->settings->turnstile_site_key;
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post(self::VERIFY_URL, array_filter([
                'secret' => $this->settings->turnstile_secret,
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (ConnectionException) {
            // Si Cloudflare no responde no se bloquea a clientes reales (hay honeypot y límite por IP).
            return true;
        }

        return (bool) $response->json('success', false);
    }

    /**
     * Comprueba solo las llaves (Ajustes > Integraciones): Cloudflare responde "invalid-input-secret" si el secreto es incorrecto.
     */
    public function testSecret(): bool
    {
        if (blank($this->settings->turnstile_secret)) {
            return false;
        }

        try {
            $codes = (array) Http::asForm()->timeout(10)->post(self::VERIFY_URL, [
                'secret' => $this->settings->turnstile_secret,
                'response' => 'test',
            ])->json('error-codes', []);
        } catch (ConnectionException) {
            return false;
        }

        return ! in_array('invalid-input-secret', $codes, true);
    }
}
