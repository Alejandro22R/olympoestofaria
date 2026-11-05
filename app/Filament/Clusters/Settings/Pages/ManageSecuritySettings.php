<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\SecuritySettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ajustes > Equipo > Seguridad: 2FA obligatorio, cierre por inactividad y bloqueo por intentos.
 */
class ManageSecuritySettings extends SettingsPage
{
    protected static string $settings = SecuritySettings::class;

    protected static ?int $navigationSort = 42;

    protected static ?string $slug = 'security';

    public static function getSectionGroup(): string
    {
        return 'team';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.security.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings.security.title'))->columns(2)->schema([
                Toggle::make('require_2fa')->label(__('settings.security.require_2fa'))
                    ->helperText(__('settings.security.require_2fa_help'))->columnSpanFull(),
                TextInput::make('idle_timeout_minutes')->label(__('settings.security.idle_timeout_minutes'))
                    ->helperText(__('settings.notifications.zero_off'))
                    ->integer()->required()->minValue(0)->maxValue(1440)->suffix('min'),
                TextInput::make('max_login_attempts')->label(__('settings.security.max_login_attempts'))
                    ->helperText(__('settings.security.max_login_attempts_help'))
                    ->integer()->required()->minValue(3)->maxValue(20),
            ]),
        ]);
    }
}
