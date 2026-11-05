<?php

namespace App\Domain\System;

use App\Domain\Alerts\DiskUsage;
use App\Domain\Settings\AlertSettings;
use App\Domain\Settings\BackupSettings;
use App\Models\HealthCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

/**
 * Estado del sistema para Ajustes > Respaldos y salud: base de datos, colas (Horizon),
 * planificador, tiempo real, disco, copias, prueba de restauración y mantenimiento.
 */
class SystemHealth
{
    public const SCHEDULER_HEARTBEAT = 'olympo:scheduler:heartbeat';

    public function __construct(
        private readonly BackupSettings $backup,
        private readonly AlertSettings $alerts,
        private readonly DiskUsage $disk,
        private readonly MaintenanceMode $maintenance,
    ) {}

    /**
     * @return array<string, array{ok: bool|null, message: string}> ok null = informativo
     */
    public function checks(): array
    {
        return [
            'database' => $this->safe(fn () => $this->database()),
            'queue' => $this->safe(fn () => $this->queue()),
            'scheduler' => $this->safe(fn () => $this->scheduler()),
            'realtime' => $this->safe(fn () => $this->realtime()),
            'disk' => $this->safe(fn () => $this->diskSpace()),
            'backup' => $this->safe(fn () => $this->lastBackup()),
            'restore_test' => $this->safe(fn () => $this->restoreTest()),
            'maintenance' => ['ok' => $this->maintenance->active() ? false : true, 'message' => __($this->maintenance->active() ? 'system.health.maintenance_on' : 'system.health.maintenance_off')],
        ];
    }

    /**
     * @return array{ok: bool|null, message: string}
     */
    private function database(): array
    {
        DB::select('select 1');
        $size = DB::connection()->getDriverName() === 'pgsql' ? (int) DB::scalar('select pg_database_size(current_database())') : null;

        return ['ok' => true, 'message' => $size ? __('system.health.db_size', ['size' => $this->bytes($size)]) : 'OK'];
    }

    /**
     * @return array{ok: bool|null, message: string}
     */
    private function queue(): array
    {
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->where('failed_at', '>', now()->subDay())->count() : 0;
        $pending = Queue::size();

        if (config('queue.default') === 'sync') {
            return ['ok' => null, 'message' => __('system.health.queue_sync')];
        }

        $running = config('queue.default') === 'redis' && count(app(MasterSupervisorRepository::class)->all()) > 0;

        return [
            'ok' => $running && $failed < $this->alerts->failed_jobs_threshold,
            'message' => __($running ? 'system.health.queue_running' : 'system.health.queue_stopped', ['pending' => $pending, 'failed' => $failed]),
        ];
    }

    /**
     * @return array{ok: bool|null, message: string}
     */
    private function scheduler(): array
    {
        $beat = Cache::get(self::SCHEDULER_HEARTBEAT);
        if ($beat === null) {
            return ['ok' => false, 'message' => __('system.health.scheduler_never')];
        }
        $at = Carbon::parse((string) $beat);

        return ['ok' => $at->gt(now()->subMinutes(5)), 'message' => __('system.health.scheduler_last', ['ago' => $at->diffForHumans()])];
    }

    /**
     * @return array{ok: bool|null, message: string}
     */
    private function realtime(): array
    {
        $host = (string) config('broadcasting.connections.reverb.options.host');
        $port = (int) config('broadcasting.connections.reverb.options.port', 443);
        $socket = @fsockopen($host, $port, $code, $error, 2);
        if ($socket !== false) {
            fclose($socket);
        }

        return ['ok' => $socket !== false, 'message' => $host.':'.$port];
    }

    /**
     * @return array{ok: bool|null, message: string}
     */
    private function diskSpace(): array
    {
        $free = $this->disk->freePercent();

        return ['ok' => $free === null ? null : $free >= $this->alerts->disk_free_percent, 'message' => $free === null ? '—' : __('system.health.disk_free', ['free' => number_format($free, 1, ',', '.')])];
    }

    /**
     * @return array{ok: bool|null, message: string}
     */
    private function lastBackup(): array
    {
        if (! $this->backup->enabled) {
            return ['ok' => false, 'message' => __('system.health.backup_disabled')];
        }
        $check = HealthCheck::latestFor('backup');
        if ($check === null) {
            return ['ok' => false, 'message' => __('system.health.backup_never')];
        }
        $maxAge = $this->backup->frequency === 'weekly' ? now()->subDays(8) : now()->subHours(26);
        $size = $check->details['size'] ?? null;

        return [
            'ok' => $check->ok && $check->checked_at->gt($maxAge),
            'message' => $check->ok
                ? __('system.health.backup_last', ['ago' => $check->checked_at->diffForHumans(), 'size' => $size ? $this->bytes((int) $size) : '—'])
                : __('system.health.backup_failed', ['ago' => $check->checked_at->diffForHumans()]),
        ];
    }

    /**
     * @return array{ok: bool|null, message: string}
     */
    private function restoreTest(): array
    {
        $check = HealthCheck::latestFor('restore_test');
        if ($check === null) {
            return ['ok' => false, 'message' => __('system.health.restore_never')];
        }

        return [
            'ok' => $check->ok && $check->checked_at->gt(now()->subDays(35)),
            'message' => $check->checked_at->diffForHumans().' · '.$check->message,
        ];
    }

    /**
     * @param  callable(): array{ok: bool|null, message: string}  $check
     * @return array{ok: bool|null, message: string}
     */
    private function safe(callable $check): array
    {
        try {
            return $check();
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => mb_substr($e->getMessage(), 0, 160)];
        }
    }

    private function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;

        return number_format($bytes / (1024 ** $i), $i === 0 ? 0 : 1, ',', '.').' '.$units[$i];
    }
}
