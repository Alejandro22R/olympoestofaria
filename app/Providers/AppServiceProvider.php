<?php

namespace App\Providers;

use App\Domain\Payments\Gateways\MercadoPagoGateway;
use App\Domain\Payments\Gateways\PaymentGateway;
use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\Listeners\AuditSettingsChanges;
use App\Http\Middleware\SetLocale;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\LaravelSettings\Events\SavingSettings;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pasarela de pago desacoplada (PLAN.md §3): hoy Mercado Pago.
        $this->app->bind(PaymentGateway::class, MercadoPagoGateway::class);
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Producción detrás de proxy/CDN con TLS: IP real del cliente y enlaces siempre https.
        TrustProxies::at((string) config('olympo.security.trusted_proxies'));
        if (config('olympo.security.force_https')) {
            URL::forceScheme('https');
        }

        // Límites de tasa (PLAN.md §7): portal público por IP, webhooks de pago por IP.
        RateLimiter::for('portal', fn (Request $request) => Limit::perMinute((int) config('olympo.security.portal_rate_limit'))->by($request->ip()));
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(240)->by($request->ip()));

        Event::listen(SavingSettings::class, AuditSettingsChanges::class);

        // Las fechas se guardan en UTC y el panel las muestra/edita en la zona horaria del taller (Ajustes > General).
        FilamentTimezone::set(fn (): string => app(GeneralSettings::class)->timezone);

        // En portugués y español los títulos no van en "Title Case" ("Ordens de serviço", no "Ordens De Serviço").
        Resource::titleCaseModelLabel(false);

        // El idioma del portal también se aplica a las peticiones de Livewire (constructor).
        Livewire::addPersistentMiddleware([SetLocale::class]);
    }
}
