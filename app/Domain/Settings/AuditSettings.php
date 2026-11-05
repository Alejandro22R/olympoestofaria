<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Sistema > Auditoría.
 */
class AuditSettings extends Settings
{
    /** Días que se conserva el registro de actividad (mínimo legal recomendado: 6 meses). */
    public int $retention_days;

    public static function group(): string
    {
        return 'audit';
    }
}
