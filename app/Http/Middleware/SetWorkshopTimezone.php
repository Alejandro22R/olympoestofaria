<?php

namespace App\Http\Middleware;

use App\Domain\Settings\GeneralSettings;
use Closure;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las fechas se guardan en UTC; el panel las muestra y edita en la zona horaria del taller (Ajustes > General).
 */
class SetWorkshopTimezone
{
    public function __construct(private readonly GeneralSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        FilamentTimezone::set($this->settings->timezone);

        return $next($request);
    }
}
