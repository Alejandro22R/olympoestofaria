<?php

namespace App\Domain\Notifications\Customer;

use App\Domain\Notifications\WhatsApp\WhatsAppClient;
use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\NotificationSettings;
use App\Domain\Settings\WhatsAppSettings;
use App\Mail\CustomerMessageMail;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Notifications\CustomerMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Throwable;

/**
 * Canal de avisos al cliente: prueba los canales en el orden de Ajustes, cae al siguiente si uno falla,
 * registra cada intento (notification_logs) y no repite un aviso ya entregado (clave de idempotencia).
 * Si todos fallan, lanza la excepción para que la cola reintente con retroceso.
 */
class CustomerChannel
{
    public function __construct(
        private readonly NotificationSettings $settings,
        private readonly WhatsAppSettings $whatsapp,
        private readonly GeneralSettings $general,
        private readonly TemplateRenderer $templates,
        private readonly WhatsAppClient $client,
    ) {}

    public function send(Customer $customer, CustomerMessage $message): void
    {
        $event = $message->event;
        $key = $message->idempotencyKey;

        if (NotificationLog::query()->where('idempotency_key', $key)->where('status', 'sent')->exists()) {
            return;
        }

        if (! CustomerConsent::allows($customer, $event)) {
            $this->log($customer, $event, 'none', null, 'skipped', $key, 'no consent');

            return;
        }

        $vars = ['nome' => $customer->name, 'oficina' => $this->general->business_name, ...$message->vars];
        $attempt = (int) NotificationLog::query()->where('idempotency_key', $key)->max('attempt') + 1;
        $errors = [];
        $tried = false;

        foreach ($this->channelsFor($customer, $event) as $channel) {
            $tried = true;
            try {
                $providerId = $channel === 'whatsapp'
                    ? $this->viaWhatsApp($customer, $event, $vars)
                    : $this->viaMail($customer, $event, $vars, $message->attachment);
                $this->log($customer, $event, $channel, $channel === 'whatsapp' ? $customer->phone : $customer->email, 'sent', $key, null, $attempt, $providerId);

                return;
            } catch (Throwable $e) {
                $errors[] = $channel.': '.$e->getMessage();
                $this->log($customer, $event, $channel, $channel === 'whatsapp' ? $customer->phone : $customer->email, 'failed', $key, mb_substr($e->getMessage(), 0, 500), $attempt);
                if (! $this->settings->fallback_enabled) {
                    break;
                }
            }
        }

        if (! $tried) {
            $this->log($customer, $event, 'none', null, 'skipped', $key, 'no available channel');

            return;
        }

        throw new RuntimeException('All channels failed: '.implode(' | ', $errors));
    }

    /**
     * Canales disponibles en el orden configurado.
     *
     * @return list<string>
     */
    public function channelsFor(Customer $customer, MessageEvent $event): array
    {
        $available = [];
        foreach ($this->settings->channel_priority as $channel) {
            $ok = match ($channel) {
                'whatsapp' => $this->whatsapp->isReady() && $customer->contact_opt_in_at !== null
                    && $this->templates->find($event, 'whatsapp', $customer->locale)?->whatsapp_template !== null,
                'mail' => $this->settings->mail_enabled && filled($customer->email)
                    && $this->templates->find($event, 'mail', $customer->locale) !== null,
                default => false,
            };
            if ($ok) {
                $available[] = $channel;
            }
        }

        return $available;
    }

    /**
     * @param  array<string, string|int|float|null>  $vars
     */
    private function viaWhatsApp(Customer $customer, MessageEvent $event, array $vars): string
    {
        $template = $this->templates->find($event, 'whatsapp', $customer->locale);
        if ($template === null || $template->whatsapp_template === null) {
            throw new RuntimeException('missing WhatsApp template');
        }

        $params = array_map(fn (string $name) => (string) ($vars[$name] ?? ''), $template->whatsapp_params ?? []);

        return $this->client->sendTemplate($customer->phone, $template->whatsapp_template, $template->whatsapp_language ?? ($customer->locale === 'es' ? 'es' : 'pt_BR'), $params);
    }

    /**
     * @param  array<string, string|int|float|null>  $vars
     * @param  array{disk: string, path: string, name: string}|null  $attachment
     */
    private function viaMail(Customer $customer, MessageEvent $event, array $vars, ?array $attachment): null
    {
        $template = $this->templates->find($event, 'mail', $customer->locale);
        if ($template === null) {
            throw new RuntimeException('missing mail template');
        }

        $unsubscribe = $event->isTransactional() ? null
            : URL::temporarySignedRoute('portal.unsubscribe', now()->addYear(), ['customer' => $customer->id]);

        Mail::to((string) $customer->email)->locale($customer->locale)->send(new CustomerMessageMail(
            TemplateRenderer::render((string) $template->subject, $vars),
            TemplateRenderer::render($template->body, $vars),
            $unsubscribe,
            $attachment,
        ));

        return null;
    }

    private function log(Customer $customer, MessageEvent $event, string $channel, ?string $recipient, string $status, string $key, ?string $error = null, int $attempt = 1, ?string $providerId = null): void
    {
        NotificationLog::query()->create([
            'notifiable_type' => $customer->getMorphClass(),
            'notifiable_id' => $customer->id,
            'event' => $event->value,
            'channel' => $channel,
            'recipient' => $recipient,
            'status' => $status,
            'attempt' => $attempt,
            'idempotency_key' => $key,
            'provider_id' => $providerId,
            'error' => $error,
        ]);
    }
}
