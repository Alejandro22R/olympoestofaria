<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de ejemplo: editables en Ajustes > Producción y Ajustes > Garantías.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('production.stalled_after_hours', 48);
        $this->migrator->add('production.late_warning_hours', 24);
        $this->migrator->add('production.progress_photos_max', 6);
        $this->migrator->add('production.unconfirmed_appointment_alert', true);

        $this->migrator->add('warranty.default_months', 3);
        $this->migrator->add('warranty.review_reminder_days', 15);
        $this->migrator->add('warranty.conditions', [
            'pt_BR' => 'A garantia cobre defeitos de costura e fixação. Não cobre mau uso, queimaduras, cortes ou produtos químicos.',
            'es' => 'La garantía cubre defectos de costura y fijación. No cubre mal uso, quemaduras, cortes ni productos químicos.',
        ]);
    }
};
