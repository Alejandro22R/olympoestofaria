<?php

namespace App\Domain\Notifications\Customer;

use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\NotificationSettings;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Notifications\CustomerMessage;
use Carbon\CarbonImmutable;

/**
 * Punto único de envío de avisos al cliente. Los no transaccionales esperan al fin del horario silencioso.
 */
class CustomerNotifier
{
    public function __construct(
        private readonly NotificationSettings $settings,
        private readonly GeneralSettings $general,
    ) {}

    /**
     * @param  array<string, string|int|float|null>  $vars
     * @param  array{disk: string, path: string, name: string}|null  $attachment
     */
    public function send(Customer $customer, MessageEvent $event, array $vars, string $idempotencyKey, ?array $attachment = null): void
    {
        // Ya entregado: no se encola otra vez (el canal vuelve a comprobarlo al enviar).
        if (NotificationLog::query()->where('idempotency_key', $idempotencyKey)->where('status', 'sent')->exists()) {
            return;
        }

        $message = new CustomerMessage($event, $vars, $idempotencyKey, $attachment);

        if (! $event->isTransactional()) {
            $now = CarbonImmutable::now($this->general->timezone);
            $allowed = $this->settings->nextAllowed($now);
            if ($allowed->gt($now)) {
                $message->delay($allowed);
            }
        }

        $customer->notify($message);
    }
}
