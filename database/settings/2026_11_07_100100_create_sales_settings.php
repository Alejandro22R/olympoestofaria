<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de ejemplo: editables en Ajustes > Cotizaciones y precios y Ajustes > Catálogo y portal.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pricing.estimate_margin_percent', 15);
        $this->migrator->add('pricing.rounding_cents', 5000);
        $this->migrator->add('pricing.show_estimate', true);

        $this->migrator->add('portal.show_prices', true);
        $this->migrator->add('portal.show_out_of_stock', true);
        $this->migrator->add('portal.headline', [
            'pt_BR' => 'Interiores sob medida, costurados à mão.',
            'es' => 'Interiores a medida, cosidos a mano.',
        ]);
        $this->migrator->add('portal.subheadline', [
            'pt_BR' => 'Bancos, volantes, portas, teto e restauração de clássicos com acabamento premium.',
            'es' => 'Asientos, volantes, puertas, techo y restauración de clásicos con terminación premium.',
        ]);
        $this->migrator->add('portal.sections', ['services', 'configurator', 'before_after', 'gallery', 'materials', 'process', 'social_proof']);
        $this->migrator->add('portal.counter_cars', 1200);
        $this->migrator->add('portal.counter_years', 15);
        $this->migrator->add('portal.counter_followers', 25000);
        $this->migrator->add('portal.instagram_url', null);
        $this->migrator->add('portal.seo_title', [
            'pt_BR' => 'Olympo Estofaria — Tapeçaria automotiva premium',
            'es' => 'Olympo Estofaria — Tapicería automotriz premium',
        ]);
        $this->migrator->add('portal.seo_description', [
            'pt_BR' => 'Bancos de couro, volantes, portas, teto e restauração de clássicos. Monte seu projeto online e receba uma estimativa.',
            'es' => 'Asientos de cuero, volantes, puertas, techo y restauración de clásicos. Arma tu proyecto online y recibe una estimación.',
        ]);
    }
};
