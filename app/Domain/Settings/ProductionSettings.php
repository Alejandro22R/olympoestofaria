<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Taller > Etapas de producción (umbrales; las etapas son una tabla editable).
 */
class ProductionSettings extends Settings
{
    /** Alerta si una orden lleva más de estas horas en la misma etapa. */
    public int $stalled_after_hours;

    /** Aviso de "a punto de atrasarse": horas antes de la entrega prometida. */
    public int $late_warning_hours;

    public int $progress_photos_max;

    /** Avisar el día anterior de las citas que siguen sin confirmar. */
    public bool $unconfirmed_appointment_alert;

    public static function group(): string
    {
        return 'production';
    }
}
