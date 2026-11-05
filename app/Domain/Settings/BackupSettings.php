<?php

namespace App\Domain\Settings;

use Carbon\CarbonImmutable;
use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Sistema > Respaldos y salud.
 */
class BackupSettings extends Settings
{
    public bool $enabled;

    /** daily | weekly */
    public string $frequency;

    /** Hora local del taller (HH:MM). */
    public string $run_at;

    /** 1 (lunes) … 7 (domingo), para la frecuencia semanal. */
    public int $weekday;

    public int $keep_days;

    /** @var list<string> Discos de destino (config/filesystems.php). */
    public array $disks;

    /** Incluir archivos privados y medios además de la base de datos. */
    public bool $include_files;

    public static function group(): string
    {
        return 'backup';
    }

    /**
     * ¿Toca respaldar en este momento local? (el planificador lo consulta cada minuto)
     */
    public function isDue(CarbonImmutable $localNow): bool
    {
        if (! $this->enabled || $localNow->format('H:i') !== $this->run_at) {
            return false;
        }

        return $this->frequency === 'daily' || $localNow->dayOfWeekIso === $this->weekday;
    }
}
