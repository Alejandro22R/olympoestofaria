<?php

namespace App\Filament\Clusters\Settings;

use App\Domain\Auth\Permission;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

/**
 * Ajustes del sistema (PLAN.md §2.6): navegación lateral por secciones, solo para el dueño.
 */
class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 100;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $slug = 'settings';

    public static function getNavigationLabel(): string
    {
        return __('settings.title');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('settings.title');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(Permission::SettingsManage->value);
    }
}
