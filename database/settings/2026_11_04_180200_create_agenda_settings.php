<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de ejemplo (capacidad de 4 vehículos, etc.): editables en Ajustes.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('agenda.working_days', [1, 2, 3, 4, 5, 6]);
        $this->migrator->add('agenda.opens_at', '08:00');
        $this->migrator->add('agenda.closes_at', '18:00');
        $this->migrator->add('agenda.vehicles_per_day', 4);
        $this->migrator->add('agenda.vehicles_per_saturday', 2);
        $this->migrator->add('agenda.min_advance_hours', 2);
        $this->migrator->add('agenda.reception_minutes', 30);
        $this->migrator->add('agenda.block_when_full', true);
    }
};
