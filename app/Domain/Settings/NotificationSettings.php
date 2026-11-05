<?php

namespace App\Domain\Settings;

use Carbon\CarbonImmutable;
use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Comunicación > Notificaciones.
 */
class NotificationSettings extends Settings
{
    public bool $mail_enabled;

    /** @var list<string> Orden de canales para el cliente (whatsapp, mail). */
    public array $channel_priority;

    /** Si un canal falla, intentar el siguiente. */
    public bool $fallback_enabled;

    /** @var list<int> Horas antes de la cita para recordar (ej. 24 y 2). */
    public array $reminder_hours;

    public string $quiet_start;

    public string $quiet_end;

    public bool $auto_send_checkin_report;

    public bool $auto_send_delivery_report;

    /** Días después de la entrega para pedir reseña (0 = no pedir). */
    public int $review_request_days;

    /** Días sin respuesta para recordar la cotización al cliente (0 = no recordar). */
    public int $quote_reminder_days;

    public int $log_retention_days;

    public static function group(): string
    {
        return 'notifications';
    }

    /**
     * ¿El instante cae en horario silencioso (en la zona del taller)?
     */
    public function inQuietHours(CarbonImmutable $localMoment): bool
    {
        $t = $localMoment->format('H:i');

        return $this->quiet_start <= $this->quiet_end
            ? $t >= $this->quiet_start && $t < $this->quiet_end
            : $t >= $this->quiet_start || $t < $this->quiet_end;
    }

    /**
     * Próximo instante permitido para avisos no urgentes.
     */
    public function nextAllowed(CarbonImmutable $localMoment): CarbonImmutable
    {
        if (! $this->inQuietHours($localMoment)) {
            return $localMoment;
        }

        [$h, $m] = array_map('intval', explode(':', $this->quiet_end));
        $end = $localMoment->setTime($h, $m);

        return $end->lte($localMoment) ? $end->addDay() : $end;
    }
}
