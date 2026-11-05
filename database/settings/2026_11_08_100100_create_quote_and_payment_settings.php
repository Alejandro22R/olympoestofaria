<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de ejemplo. Mercado Pago empieza DESACTIVADO y en modo de prueba:
 * el dueño lo conecta en Ajustes > Pagos con sus credenciales.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pricing.quote_validity_days', 7);
        $this->migrator->add('pricing.max_discount_percent', 10);
        $this->migrator->add('pricing.final_price_roles', ['owner', 'reception']);
        $this->migrator->add('pricing.require_contact_before_quote', false);

        $this->migrator->add('payments.deposit_percent', 30);
        $this->migrator->add('payments.deposit_required_to_confirm', true);
        $this->migrator->add('payments.balance_moment', 'on_delivery');
        $this->migrator->add('payments.manual_methods', ['cash', 'pix', 'transfer']);

        $this->migrator->add('payments.mercadopago_enabled', false);
        $this->migrator->add('payments.mercadopago_mode', 'sandbox');
        $this->migrator->add('payments.mercadopago_methods', ['pix']);
        $this->migrator->add('payments.mercadopago_public_key', null);
        $this->migrator->addEncrypted('payments.mercadopago_access_token', null);
        $this->migrator->addEncrypted('payments.mercadopago_webhook_secret', null);
        $this->migrator->add('payments.pix_expiration_minutes', 30);
        $this->migrator->add('payments.statement_descriptor', 'OLYMPO');
    }
};
