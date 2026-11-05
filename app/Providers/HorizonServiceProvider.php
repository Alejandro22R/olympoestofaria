<?php

namespace App\Providers;

use App\Domain\Auth\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // Solo el dueño activo ve el panel de colas (Ajustes > Respaldos y salud enlaza a /horizon).
        Gate::define('viewHorizon', fn ($user = null) => $user instanceof User && $user->is_active && $user->can(Permission::SettingsManage->value));
    }
}
