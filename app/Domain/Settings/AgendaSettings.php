<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Taller > Agenda y capacidad.
 */
class AgendaSettings extends Settings
{
    /**
     * Días laborables ISO-8601 (1 = lunes … 7 = domingo).
     *
     * @var list<int>
     */
    public array $working_days;

    public string $opens_at;

    public string $closes_at;

    /** Vehículos que se pueden recibir por día (lunes a viernes). */
    public int $vehicles_per_day;

    /** Vehículos por día en sábado. */
    public int $vehicles_per_saturday;

    /** Anticipación mínima para agendar, en horas. */
    public int $min_advance_hours;

    /** Duración estimada de la recepción, en minutos. */
    public int $reception_minutes;

    /** Si true, un día lleno no admite más citas; si false, solo avisa. */
    public bool $block_when_full;

    public static function group(): string
    {
        return 'agenda';
    }
}
