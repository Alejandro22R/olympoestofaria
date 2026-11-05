<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de ejemplo (editables). WhatsApp Cloud API empieza desactivado.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        // Ajustes > Comunicación > Notificaciones
        $this->migrator->add('notifications.mail_enabled', true);
        $this->migrator->add('notifications.channel_priority', ['whatsapp', 'mail']);
        $this->migrator->add('notifications.fallback_enabled', true);
        $this->migrator->add('notifications.reminder_hours', [24, 2]);
        $this->migrator->add('notifications.quiet_start', '21:00');
        $this->migrator->add('notifications.quiet_end', '08:00');
        $this->migrator->add('notifications.auto_send_checkin_report', true);
        $this->migrator->add('notifications.auto_send_delivery_report', true);
        $this->migrator->add('notifications.review_request_days', 3);
        $this->migrator->add('notifications.quote_reminder_days', 2);
        $this->migrator->add('notifications.log_retention_days', 180);

        // Ajustes > Sistema > Integraciones > WhatsApp Cloud API
        $this->migrator->add('whatsapp.enabled', false);
        $this->migrator->add('whatsapp.phone_number_id', null);
        $this->migrator->add('whatsapp.business_account_id', null);
        $this->migrator->addEncrypted('whatsapp.access_token', null);
        $this->migrator->add('whatsapp.api_version', 'v21.0');

        // Ajustes > Comunicación > Alertas automáticas
        $this->migrator->add('alerts.lead_unanswered_hours', 4);
        $this->migrator->add('alerts.quote_unanswered_days', 3);
        $this->migrator->add('alerts.deposit_overdue_hours', 48);
        $this->migrator->add('alerts.balance_overdue_days', 2);
        $this->migrator->add('alerts.escalation_hours', 4);
        $this->migrator->add('alerts.escalate_by_mail', true);
        $this->migrator->add('alerts.failed_jobs_threshold', 5);
        $this->migrator->add('alerts.disk_free_percent', 10);

        // Ajustes > Equipo > Usuarios y roles (seguridad)
        $this->migrator->add('security.require_2fa', false);
        $this->migrator->add('security.idle_timeout_minutes', 120);
        $this->migrator->add('security.max_login_attempts', 5);

        // Ajustes > Sistema > Marca y apariencia
        $this->migrator->add('brand.logo_dark_path', null);
        $this->migrator->add('brand.logo_light_path', null);
        $this->migrator->add('brand.favicon_path', null);
        $this->migrator->add('brand.primary_color', '#0B8FD9');
        $this->migrator->add('brand.default_theme', 'dark');
        $this->migrator->add('brand.reduce_motion', false);

        // Integraciones opcionales
        $this->migrator->add('integrations.turnstile_enabled', false);
        $this->migrator->add('integrations.turnstile_site_key', null);
        $this->migrator->addEncrypted('integrations.turnstile_secret', null);
    }
};
