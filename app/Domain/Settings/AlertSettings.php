<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Comunicación > Alertas automáticas (umbrales y escalamiento al dueño).
 */
class AlertSettings extends Settings
{
    public int $lead_unanswered_hours;

    public int $quote_unanswered_days;

    public int $deposit_overdue_hours;

    public int $balance_overdue_days;

    /** Si nadie atiende (lee) la alerta en estas horas, se escala al dueño. 0 = no escalar. */
    public int $escalation_hours;

    public bool $escalate_by_mail;

    public int $failed_jobs_threshold;

    public int $disk_free_percent;

    public static function group(): string
    {
        return 'alerts';
    }
}
