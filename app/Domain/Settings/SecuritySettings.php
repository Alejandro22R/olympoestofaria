<?php

namespace App\Domain\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ajustes > Equipo > Usuarios y roles: verificación en dos pasos, inactividad e intentos.
 */
class SecuritySettings extends Settings
{
    public bool $require_2fa;

    public int $idle_timeout_minutes;

    public int $max_login_attempts;

    public static function group(): string
    {
        return 'security';
    }
}
