<?php

namespace App\Http\Middleware;

use App\Domain\Settings\SecuritySettings;
use App\Support\Brand;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Illuminate\Http\Request;

/**
 * 2FA obligatorio según Ajustes > Seguridad, leído en cada petición (Filament lo evalúa
 * al registrar rutas, por eso el panel lo declara siempre y aquí se decide).
 */
class EnsureTwoFactorWhenRequired extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Brand::settingsValue(SecuritySettings::class, 'require_2fa', false)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
