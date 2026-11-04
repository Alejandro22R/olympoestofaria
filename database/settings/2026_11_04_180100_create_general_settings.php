<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de ejemplo: se confirman con el taller antes del lanzamiento.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.business_name', 'Olympo Estofaria');
        $this->migrator->add('general.city', 'São Paulo');
        $this->migrator->add('general.whatsapp', null);
        $this->migrator->add('general.email', null);
        $this->migrator->add('general.maps_url', null);
        $this->migrator->add('general.default_locale', 'pt_BR');
        $this->migrator->add('general.portal_locales', ['pt_BR', 'es']);
        $this->migrator->add('general.timezone', 'America/Sao_Paulo');
        $this->migrator->add('general.currency', 'BRL');
        $this->migrator->add('general.country', 'BR');
    }
};
