<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Integrations\IntegrationStatus;
use App\Domain\Settings\IntegrationSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Ajustes > Sistema > Integraciones: estado y prueba de cada servicio conectado, y la
 * configuración de Cloudflare Turnstile (anti-spam). El secreto es de solo escritura.
 */
class ManageIntegrationSettings extends SettingsPage
{
    protected static string $settings = IntegrationSettings::class;

    protected static ?int $navigationSort = 51;

    protected static ?string $slug = 'integrations';

    public static function getSectionGroup(): string
    {
        return 'system';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.integrations.title');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['turnstile_secret'] = null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['turnstile_secret'] ?? null)) {
            unset($data['turnstile_secret']);
        } else {
            $data['turnstile_secret'] = trim((string) $data['turnstile_secret']);
        }

        return $data;
    }

    public function testService(string $service): void
    {
        abort_unless(static::canAccess() && in_array($service, IntegrationStatus::SERVICES, true), 403);
        $result = app(IntegrationStatus::class)->test($service, auth()->user());
        Notification::make()->title($result->message)->{$result->ok ? 'success' : 'danger'}()->send();
    }

    public function form(Schema $schema): Schema
    {
        $status = app(IntegrationStatus::class);
        $hasSecret = filled(app(IntegrationSettings::class)->turnstile_secret);

        $rows = collect(IntegrationStatus::SERVICES)->map(function (string $service) use ($status) {
            $s = $status->status($service);

            return Grid::make(3)->schema([
                TextEntry::make("status_{$service}")->hiddenLabel()
                    ->state(__('settings.integrations.services.'.$service))->weight('bold'),
                TextEntry::make("state_{$service}")->hiddenLabel()->badge()
                    ->state(__('settings.integrations.states.'.$s['state']).($s['detail'] !== '' ? ' · '.$s['detail'] : ''))
                    ->color(match ($s['state']) {
                        'ok' => 'success', 'missing' => 'warning', default => 'gray'
                    }),
                Actions::make([
                    Action::make("test_{$service}")->label(__('payments.settings.test_connection'))
                        ->icon(Heroicon::OutlinedSignal)->color('gray')->size('sm')
                        ->action(fn () => $this->testService($service)),
                ])->visible($service !== 'sentry'),
            ]);
        })->all();

        return $schema->components([
            Section::make(__('settings.integrations.status'))->description(__('settings.integrations.status_help'))->schema($rows),
            Section::make('Cloudflare Turnstile')->description(__('settings.integrations.turnstile_help'))->columns(2)->schema([
                Toggle::make('turnstile_enabled')->label(__('settings.integrations.turnstile_enabled'))->live()->columnSpanFull(),
                TextInput::make('turnstile_site_key')->label('Site key')->maxLength(100)
                    ->required(fn (Get $get) => (bool) $get('turnstile_enabled')),
                TextInput::make('turnstile_secret')->label('Secret key')
                    ->password()->revealable(false)->autocomplete('new-password')->maxLength(200)
                    ->placeholder($hasSecret ? __('payments.settings.secret_set') : __('payments.settings.secret_missing'))
                    ->helperText(__('payments.settings.secret_help'))
                    ->required(fn (Get $get) => (bool) $get('turnstile_enabled') && ! $hasSecret),
            ]),
        ]);
    }
}
