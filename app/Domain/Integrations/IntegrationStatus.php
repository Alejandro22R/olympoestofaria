<?php

namespace App\Domain\Integrations;

use App\Domain\Notifications\WhatsApp\WhatsAppClient;
use App\Domain\Payments\Gateways\ConnectionResult;
use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Settings\NotificationSettings;
use App\Domain\Settings\PaymentSettings;
use App\Domain\Settings\WhatsAppSettings;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Estado y prueba de cada servicio conectado (Ajustes > Sistema > Integraciones).
 * El estado se calcula sin llamar a terceros; las pruebas sí se conectan.
 */
class IntegrationStatus
{
    public const SERVICES = ['mercadopago', 'whatsapp', 'mail', 'storage', 'realtime', 'turnstile', 'sentry'];

    public function __construct(
        private readonly PaymentSettings $payments,
        private readonly WhatsAppSettings $whatsapp,
        private readonly NotificationSettings $notifications,
        private readonly Turnstile $turnstile,
    ) {}

    /**
     * @return array{state: string, detail: string} state: ok | off | missing
     */
    public function status(string $service): array
    {
        return match ($service) {
            'mercadopago' => ! $this->payments->mercadopago_enabled ? $this->state('off')
                : (filled($this->payments->mercadopago_access_token) ? $this->state('ok', __('payments.modes.'.$this->payments->mercadopago_mode)) : $this->state('missing')),
            'whatsapp' => ! $this->whatsapp->enabled ? $this->state('off') : ($this->whatsapp->isReady() ? $this->state('ok') : $this->state('missing')),
            'mail' => ! $this->notifications->mail_enabled ? $this->state('off')
                : (in_array(config('mail.default'), ['log', 'array'], true) ? $this->state('missing', __('settings.integrations.mail_log')) : $this->state('ok', (string) config('mail.default'))),
            'storage' => $this->state('ok', (string) config('filesystems.disks.private.driver', 'local')),
            'realtime' => config('broadcasting.default') === 'reverb' && filled(config('broadcasting.connections.reverb.key'))
                ? $this->state('ok', (string) config('broadcasting.connections.reverb.options.host')) : $this->state('missing'),
            'turnstile' => $this->turnstile->enabled() ? $this->state('ok') : $this->state('off'),
            'sentry' => filled(config('sentry.dsn')) ? $this->state('ok') : $this->state('off', __('settings.integrations.phase7')),
            default => $this->state('missing'),
        };
    }

    public function test(string $service, ?User $user = null): ConnectionResult
    {
        try {
            return match ($service) {
                'mercadopago' => app(PaymentGateway::class)->testConnection(),
                'whatsapp' => app(WhatsAppClient::class)->testConnection(),
                'mail' => $this->testMail($user),
                'storage' => $this->testStorage(),
                'realtime' => $this->testRealtime(),
                'turnstile' => $this->turnstile->testSecret()
                    ? new ConnectionResult(true, __('settings.integrations.test_ok'))
                    : new ConnectionResult(false, __('settings.integrations.turnstile_invalid')),
                default => new ConnectionResult(false, __('settings.integrations.not_testable')),
            };
        } catch (Throwable $e) {
            report($e);

            return new ConnectionResult(false, __('settings.integrations.test_failed', ['message' => Str::limit($e->getMessage(), 120)]));
        }
    }

    private function testMail(?User $user): ConnectionResult
    {
        if ($user === null) {
            return new ConnectionResult(false, __('settings.integrations.not_testable'));
        }

        Mail::raw(__('settings.integrations.mail_test_body'), fn ($m) => $m->to($user->email)->subject(__('settings.integrations.mail_test_subject')));

        return new ConnectionResult(true, __('settings.integrations.mail_sent', ['email' => $user->email]));
    }

    private function testStorage(): ConnectionResult
    {
        $disk = Storage::disk('private');
        $path = '.health/'.Str::uuid()->toString();
        $disk->put($path, 'ok');
        $ok = $disk->get($path) === 'ok';
        $disk->delete($path);

        return new ConnectionResult($ok, $ok ? __('settings.integrations.test_ok') : __('settings.integrations.storage_failed'));
    }

    private function testRealtime(): ConnectionResult
    {
        $host = (string) config('broadcasting.connections.reverb.options.host');
        $port = (int) config('broadcasting.connections.reverb.options.port', 443);
        $socket = @fsockopen($host, $port, $code, $message, 3);
        if ($socket === false) {
            return new ConnectionResult(false, __('settings.integrations.realtime_down', ['host' => $host.':'.$port]));
        }
        fclose($socket);

        return new ConnectionResult(true, __('settings.integrations.test_ok'));
    }

    /**
     * @return array{state: string, detail: string}
     */
    private function state(string $state, string $detail = ''): array
    {
        return ['state' => $state, 'detail' => $detail];
    }
}
