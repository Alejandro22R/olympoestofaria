<?php

namespace App\Providers;

use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\Listeners\AuditSettingsChanges;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\Events\SavingSettings;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Event::listen(SavingSettings::class, AuditSettingsChanges::class);

        // Las fechas se guardan en UTC y el panel las muestra/edita en la zona horaria del taller (Ajustes > General).
        FilamentTimezone::set(fn (): string => app(GeneralSettings::class)->timezone);

        // En portugués y español los títulos no van en "Title Case" ("Ordens de serviço", no "Ordens De Serviço").
        Resource::titleCaseModelLabel(false);
    }
}
