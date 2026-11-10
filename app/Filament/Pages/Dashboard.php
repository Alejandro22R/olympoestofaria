<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Inicio del panel: accesos rápidos y "Hoy" para todos; cifras del negocio solo para el dueño.
 */
class Dashboard extends BaseDashboard
{
    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.title');
    }
}
