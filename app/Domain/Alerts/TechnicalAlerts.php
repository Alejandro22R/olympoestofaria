<?php

namespace App\Domain\Alerts;

use App\Domain\Notifications\StaffNotifier;
use App\Domain\Settings\AlertSettings;
use App\Models\AlertLog;
use App\Models\WebhookEvent;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alertas técnicas, solo para el dueño: cola con trabajos fallidos, problemas en webhooks de pago
 * y disco casi lleno. (Copias de seguridad y Sentry: Fase 7.)
 */
class TechnicalAlerts
{
    public function __construct(
        private readonly AlertSettings $settings,
        private readonly StaffNotifier $notifier,
        private readonly DiskUsage $disk,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return [
            'failed_jobs' => $this->failedJobs(),
            'webhooks' => $this->webhooks(),
            'disk' => $this->disk(),
        ];
    }

    private function today(): string
    {
        return now()->toDateString();
    }

    private function failedJobs(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }
        $failed = DB::table('failed_jobs')->where('failed_at', '>', now()->subDay())->count();
        if ($failed < $this->settings->failed_jobs_threshold || ! AlertLog::claim("failed_jobs:{$this->today()}", 'technical')) {
            return 0;
        }

        $this->notifier->notifyOwners(new StaffAlert(type: 'technical', titleKey: 'notifications.failed_jobs', params: ['count' => $failed],
            bodyKey: 'notifications.failed_jobs_body', color: 'danger', icon: 'heroicon-o-exclamation-triangle', forceMail: true));

        return 1;
    }

    private function webhooks(): int
    {
        $problems = WebhookEvent::query()->where('created_at', '>', now()->subDay())
            ->whereIn('result', ['amount_mismatch', 'unknown_payment'])->count();
        if ($problems === 0 || ! AlertLog::claim("webhooks:{$this->today()}", 'technical')) {
            return 0;
        }

        $this->notifier->notifyOwners(new StaffAlert(type: 'technical', titleKey: 'notifications.webhook_problems', params: ['count' => $problems],
            bodyKey: 'notifications.webhook_problems_body', color: 'danger', icon: 'heroicon-o-exclamation-triangle', forceMail: true));

        return 1;
    }

    public function freePercent(): ?float
    {
        return $this->disk->freePercent();
    }

    private function disk(): int
    {
        $free = $this->freePercent();
        if ($free === null || $free >= $this->settings->disk_free_percent || ! AlertLog::claim("disk:{$this->today()}", 'technical')) {
            return 0;
        }

        $this->notifier->notifyOwners(new StaffAlert(type: 'technical', titleKey: 'notifications.disk_low', params: ['free' => number_format($free, 1, ',', '.')],
            color: 'danger', icon: 'heroicon-o-server', forceMail: true));

        return 1;
    }
}
