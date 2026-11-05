<?php

namespace App\Http\Middleware;

use App\Domain\Settings\SecuritySettings;
use App\Support\Brand;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión del panel tras N minutos sin actividad (Ajustes > Seguridad; 0 = desactivado).
 */
class EnforceIdleTimeout
{
    public const KEY = 'panel.last_activity';

    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) Brand::settingsValue(SecuritySettings::class, 'idle_timeout_minutes', 0);

        if ($minutes > 0 && $request->hasSession() && $request->user() !== null) {
            $last = (int) $request->session()->get(self::KEY, 0);

            if ($last > 0 && now()->timestamp - $last > $minutes * 60) {
                Filament::auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new AuthenticationException('Unauthenticated.', [], Filament::getLoginUrl());
            }

            $request->session()->put(self::KEY, now()->timestamp);
        }

        return $next($request);
    }
}
