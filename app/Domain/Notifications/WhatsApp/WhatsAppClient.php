<?php

namespace App\Domain\Notifications\WhatsApp;

use App\Domain\Payments\Gateways\ConnectionResult;
use App\Domain\Settings\WhatsAppSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * WhatsApp Cloud API (Meta Graph API). Fuera de la ventana de 24 h solo se pueden enviar
 * plantillas aprobadas por Meta, así que todo envío usa plantilla con parámetros.
 */
class WhatsAppClient
{
    public const BASE_URL = 'https://graph.facebook.com';

    public function __construct(private readonly WhatsAppSettings $settings) {}

    /**
     * @param  list<string>  $params  parámetros del cuerpo en orden
     * @return string id del mensaje en Meta
     */
    public function sendTemplate(string $toE164, string $template, string $language, array $params): string
    {
        if (! $this->settings->isReady()) {
            throw new RuntimeException('WhatsApp Cloud API not configured');
        }

        $components = $params === [] ? [] : [[
            'type' => 'body',
            'parameters' => array_map(fn (string $p) => ['type' => 'text', 'text' => mb_substr($p, 0, 1000)], $params),
        ]];

        try {
            $response = Http::baseUrl(self::BASE_URL.'/'.$this->settings->api_version)
                ->withToken((string) $this->settings->access_token)->acceptJson()->timeout(15)
                ->post('/'.$this->settings->phone_number_id.'/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => ltrim($toE164, '+'),
                    'type' => 'template',
                    'template' => array_filter([
                        'name' => $template,
                        'language' => ['code' => $language],
                        'components' => $components ?: null,
                    ]),
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('WhatsApp unreachable: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp error: '.($response->json('error.message') ?? $response->status()));
        }

        return (string) $response->json('messages.0.id');
    }

    public function testConnection(): ConnectionResult
    {
        if (blank($this->settings->phone_number_id) || blank($this->settings->access_token)) {
            return new ConnectionResult(false, __('communication.whatsapp.not_configured'));
        }

        try {
            $response = Http::baseUrl(self::BASE_URL.'/'.$this->settings->api_version)
                ->withToken((string) $this->settings->access_token)->acceptJson()->timeout(15)
                ->get('/'.$this->settings->phone_number_id, ['fields' => 'display_phone_number,verified_name']);
        } catch (ConnectionException) {
            return new ConnectionResult(false, __('communication.whatsapp.unreachable'));
        }

        if ($response->failed()) {
            return new ConnectionResult(false, __('communication.whatsapp.error', ['message' => (string) ($response->json('error.message') ?? $response->status())]));
        }

        return new ConnectionResult(true, __('communication.whatsapp.ok', [
            'number' => (string) $response->json('display_phone_number'),
            'name' => (string) $response->json('verified_name'),
        ]));
    }
}
