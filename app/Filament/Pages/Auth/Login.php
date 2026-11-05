<?php

namespace App\Filament\Pages\Auth;

use App\Domain\Settings\SecuritySettings;
use App\Support\Brand;
use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Inicio de sesión del panel con el límite de intentos por minuto de Ajustes > Seguridad
 * (reemplaza el fijo de 5 de Filament; también aplica al desafío de 2FA).
 */
class Login extends BaseLogin
{
    /**
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @param  string|null  $method
     * @param  string|null  $component
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null): void
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 2)[1]['function'];
        $max = max(1, (int) Brand::settingsValue(SecuritySettings::class, 'max_login_attempts', $maxAttempts));

        parent::rateLimit($max, $decaySeconds, $method, $component);
    }
}
