<?php

namespace App\Providers;

use App\Domain\Settings\Listeners\AuditSettingsChanges;
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
    }
}
