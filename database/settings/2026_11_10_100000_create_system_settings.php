<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Fase 7: privacidad/LGPD, respaldos y auditoría (valores iniciales editables).
 * Los textos legales son una base que el taller debe revisar con su abogado.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $legal = fn (string $doc) => [
            'pt_BR' => (string) file_get_contents(resource_path("legal/{$doc}.pt_BR.md")),
            'es' => (string) file_get_contents(resource_path("legal/{$doc}.es.md")),
        ];

        // Ajustes > Sistema > Privacidad y LGPD
        $this->migrator->add('privacy.store_document_photo', true);
        $this->migrator->add('privacy.inactive_customer_months', 0);
        $this->migrator->add('privacy.lost_lead_months', 24);
        $this->migrator->add('privacy.privacy_policy', $legal('privacy'));
        $this->migrator->add('privacy.terms', $legal('terms'));
        $this->migrator->add('privacy.legal_updated_at', '2026-11-10');
        $this->migrator->add('privacy.contact_email', null);

        // Ajustes > Sistema > Respaldos y salud
        $this->migrator->add('backup.enabled', true);
        $this->migrator->add('backup.frequency', 'daily');
        $this->migrator->add('backup.run_at', '03:00');
        $this->migrator->add('backup.weekday', 7);
        $this->migrator->add('backup.keep_days', 14);
        $this->migrator->add('backup.disks', ['backups']);
        $this->migrator->add('backup.include_files', true);

        // Ajustes > Sistema > Auditoría
        $this->migrator->add('audit.retention_days', 730);
    }
};
