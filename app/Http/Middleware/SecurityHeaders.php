<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas web.
 * CSP: scripts, estilos, conexiones y marcos solo de orígenes propios y de los servicios que el
 * sistema usa (Turnstile, Reverb, Sentry). Livewire, Alpine y Filament necesitan scripts en línea
 * y 'unsafe-eval', así que la protección principal es la lista de orígenes.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        if (config('olympo.security.hsts') && $request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        if (config('olympo.security.csp') && ! $response->headers->has('Content-Security-Policy')) {
            $headers['Content-Security-Policy'] = $this->csp();
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        return $response;
    }

    private function csp(): string
    {
        $socketHost = (string) config('olympo.security.socket_host');
        $socketPort = (string) config('olympo.security.socket_port');
        $sockets = $socketHost !== '' ? "wss://{$socketHost}:{$socketPort} ws://{$socketHost}:{$socketPort} wss://{$socketHost} ws://{$socketHost}" : '';
        $sentry = ($dsn = config('sentry.dsn')) && ($host = parse_url((string) $dsn, PHP_URL_HOST)) ? 'https://'.$host : '';

        return implode('; ', array_filter([
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "media-src 'self' blob:",
            trim("connect-src 'self' {$sockets} {$sentry}"),
            'frame-src https://challenges.cloudflare.com',
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));
    }
}
