<?php

namespace App\Domain\System;

use App\Domain\Notifications\StaffNotifier;
use App\Domain\Settings\BackupSettings;
use App\Models\HealthCheck;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Copia de seguridad con spatie/laravel-backup usando Ajustes > Respaldos (destinos, archivos y
 * retención). Base de datos siempre; archivos privados y públicos si está activado.
 * El resultado queda en "health_checks" y un fallo avisa al dueño (alerta técnica por correo).
 */
class RunBackup
{
    public const BACKUP_NAME = 'olympo';

    public function __construct(
        private readonly BackupSettings $settings,
        private readonly StaffNotifier $notifier,
    ) {}

    public function configure(): void
    {
        $available = (array) config('olympo.backup_disks');
        $disks = array_values(array_intersect($this->settings->disks, $available)) ?: ['backups'];

        config([
            'backup.backup.name' => self::BACKUP_NAME,
            'backup.backup.destination.disks' => $disks,
            'backup.monitor_backups.0.disks' => $disks,
            'backup.monitor_backups.0.name' => self::BACKUP_NAME,
            'backup.cleanup.default_strategy.keep_all_backups_for_days' => $this->settings->keep_days,
            'backup.cleanup.default_strategy.keep_daily_backups_for_days' => max(16, $this->settings->keep_days),
        ]);
    }

    public function execute(): bool
    {
        $this->configure();

        try {
            $code = Artisan::call('backup:run', array_filter([
                '--only-db' => ! $this->settings->include_files,
                '--disable-notifications' => true,
                // Relee config('backup') con los destinos y la retención de Ajustes.
                '--config' => 'backup',
            ]));
            $output = trim(Artisan::output());
            Artisan::call('backup:clean', ['--disable-notifications' => true, '--config' => 'backup']);
        } catch (Throwable $e) {
            $code = 1;
            $output = $e->getMessage();
        }

        $ok = $code === 0 && ! str_contains($output, 'failed');
        $newest = $ok ? app(BackupStatus::class)->newest() : null;

        HealthCheck::record('backup', $ok, $ok ? null : mb_substr($output, -800), array_filter([
            'disks' => config('backup.backup.destination.disks'),
            'size' => $newest['size'] ?? null,
            'path' => $newest['path'] ?? null,
        ]));

        if (! $ok) {
            $this->notifier->notifyOwners(new StaffAlert(
                type: 'technical', titleKey: 'notifications.backup_failed', bodyKey: 'notifications.backup_failed_body',
                color: 'danger', icon: 'heroicon-o-circle-stack', forceMail: true,
            ));
        }

        return $ok;
    }
}
