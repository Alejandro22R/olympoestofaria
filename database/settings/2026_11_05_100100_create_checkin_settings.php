<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de ejemplo del check-in: editables en Ajustes > Check-in.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('checkin.mileage_required', true);
        $this->migrator->add('checkin.max_photos_per_mark', 4);
        $this->migrator->add('checkin.max_marks', 30);
        $this->migrator->add('checkin.report_link_days', 7);
        $this->migrator->add('checkin.legal_text', [
            'pt_BR' => 'Este termo registra o estado do veículo no momento da recepção na oficina. O cliente pode solicitar correções em até 24 horas após o recebimento deste documento.',
            'es' => 'Esta acta registra el estado del vehículo al momento de la recepción en el taller. El cliente puede solicitar correcciones hasta 24 horas después de recibir este documento.',
        ]);
        $this->migrator->add('checkin.consent_text', [
            'pt_BR' => 'Autorizo o registro de fotos e dados do veículo para fins de vistoria e garantia (LGPD).',
            'es' => 'Autorizo el registro de fotos y datos del vehículo para inspección y garantía (LGPD).',
        ]);
    }
};
