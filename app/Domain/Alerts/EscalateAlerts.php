<?php

namespace App\Domain\Alerts;

use App\Domain\Notifications\StaffNotifier;
use App\Domain\Settings\AlertSettings;
use App\Models\AlertLog;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\DB;

/**
 * Escalamiento: si nadie leyó una alerta en las horas configuradas, se avisa al dueño
 * (campana y, si está activado, correo). Una sola vez por alerta.
 */
class EscalateAlerts
{
    public function __construct(
        private readonly AlertSettings $settings,
        private readonly StaffNotifier $notifier,
    ) {}

    public function execute(): int
    {
        if ($this->settings->escalation_hours <= 0) {
            return 0;
        }

        $count = 0;
        $pending = AlertLog::query()->whereNull('escalated_at')->where('type', '!=', 'technical')
            ->where('created_at', '<', now()->subHours($this->settings->escalation_hours))
            ->where('created_at', '>', now()->subDays(7))->get();

        foreach ($pending as $log) {
            $notifications = DB::table('notifications')->whereRaw("data->>'alert_key' = ?", [$log->key]);
            $exists = (clone $notifications)->exists();
            $read = (clone $notifications)->whereNotNull('read_at')->exists();

            if (! $exists || ! $read) {
                $title = DB::table('notifications')->whereRaw("data->>'alert_key' = ?", [$log->key])->selectRaw("data->>'title' as title")->value('title');
                $this->notifier->notifyOwners(new StaffAlert(
                    type: 'escalated',
                    titleKey: 'notifications.escalated',
                    params: ['title' => (string) ($title ?? $log->type), 'hours' => $this->settings->escalation_hours],
                    bodyKey: 'notifications.escalated_body',
                    color: 'danger',
                    icon: 'heroicon-o-arrow-trending-up',
                    forceMail: $this->settings->escalate_by_mail,
                ));
                $count++;
            }

            $log->forceFill(['escalated_at' => now()])->save();
        }

        return $count;
    }
}
