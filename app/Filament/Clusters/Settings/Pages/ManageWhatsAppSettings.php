<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Notifications\WhatsApp\WhatsAppClient;
use App\Domain\Settings\WhatsAppSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Ajustes > Comunicación > WhatsApp (Cloud API de Meta). El token es de solo escritura: cifrado,
 * nunca vuelve al navegador; vacío al guardar = conservar el actual.
 */
class ManageWhatsAppSettings extends SettingsPage
{
    protected static string $settings = WhatsAppSettings::class;

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'whatsapp';

    public static function getSectionGroup(): string
    {
        return 'communication';
    }

    public static function getNavigationLabel(): string
    {
        return 'WhatsApp';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['access_token'] = null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['access_token'] ?? null)) {
            unset($data['access_token']);
        } else {
            $data['access_token'] = trim((string) $data['access_token']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label(__('payments.settings.test_connection'))
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->action(function (): void {
                    $result = app(WhatsAppClient::class)->testConnection();
                    Notification::make()->title($result->message)->{$result->ok ? 'success' : 'danger'}()->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $hasToken = filled(app(WhatsAppSettings::class)->access_token);

        return $schema->components([
            Section::make('WhatsApp Cloud API')
                ->description(__('settings.whatsapp.help'))
                ->columns(2)
                ->schema([
                    Toggle::make('enabled')->label(__('settings.whatsapp.enabled'))->live()->columnSpanFull()
                        ->helperText(__('settings.whatsapp.enabled_help')),
                    TextInput::make('phone_number_id')->label(__('settings.whatsapp.phone_number_id'))
                        ->maxLength(40)->regex('/^\d+$/')
                        ->required(fn (Get $get) => (bool) $get('enabled')),
                    TextInput::make('business_account_id')->label(__('settings.whatsapp.business_account_id'))
                        ->maxLength(40)->regex('/^\d*$/'),
                    TextInput::make('access_token')->label(__('settings.whatsapp.access_token'))
                        ->password()->revealable(false)->autocomplete('new-password')->maxLength(600)
                        ->placeholder($hasToken ? __('payments.settings.secret_set') : __('payments.settings.secret_missing'))
                        ->helperText(__('payments.settings.secret_help'))
                        ->required(fn (Get $get) => (bool) $get('enabled') && ! $hasToken),
                    TextInput::make('api_version')->label(__('settings.whatsapp.api_version'))
                        ->required()->regex('/^v\d+\.\d+$/')->maxLength(10),
                ]),
        ]);
    }
}
