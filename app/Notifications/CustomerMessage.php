<?php

namespace App\Notifications;

use App\Domain\Notifications\Customer\CustomerChannel;
use App\Domain\Notifications\Customer\MessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Aviso al cliente por el canal que corresponda (WhatsApp, correo) con respaldo,
 * reintentos con retroceso exponencial y clave de idempotencia.
 */
class CustomerMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /**
     * @param  array<string, string|int|float|null>  $vars
     * @param  array{disk: string, path: string, name: string}|null  $attachment
     */
    public function __construct(
        public MessageEvent $event,
        public array $vars,
        public string $idempotencyKey,
        public ?array $attachment = null,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [CustomerChannel::class];
    }
}
