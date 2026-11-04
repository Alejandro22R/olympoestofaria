<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idioma de la interfaz: preferencia del usuario, luego sesión (?lang=), luego el predeterminado.
 */
class SetLocale
{
    public const SUPPORTED = ['pt_BR', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, self::SUPPORTED, true) && $request->hasSession()) {
            $request->session()->put('locale', $requested);
        }

        $locale = $request->user()->locale
            ?? ($request->hasSession() ? $request->session()->get('locale') : null)
            ?? config('app.locale');

        if (in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
