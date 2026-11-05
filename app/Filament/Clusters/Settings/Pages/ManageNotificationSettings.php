<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\NotificationSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ajustes > Comunicación > Notificaciones: canales, respaldo, recordatorios, horario silencioso,
 * envíos automáticos y retención del registro.
 */
class ManageNotificationSettings extends SettingsPage
{
    protected static string $settings = NotificationSettings::class;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'notifications';

    public static function getSectionGroup(): string
    {
        return 'communication';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.notifications.title');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['reminder_hours'] = array_map('strval', $data['reminder_hours'] ?? []);
        $data['primary_channel'] = $data['channel_priority'][0] ?? 'whatsapp';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $hours = array_unique(array_filter(array_map('intval', (array) ($data['reminder_hours'] ?? [])), fn (int $h) => $h > 0 && $h <= 168));
        rsort($hours);
        $data['reminder_hours'] = $hours;
        $data['channel_priority'] = array_values(array_unique(array_filter([$data['primary_channel'] ?? 'whatsapp', ($data['primary_channel'] ?? 'whatsapp') === 'whatsapp' ? 'mail' : 'whatsapp'])));
        unset($data['primary_channel']);
        $data['quiet_start'] = substr((string) $data['quiet_start'], 0, 5);
        $data['quiet_end'] = substr((string) $data['quiet_end'], 0, 5);

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings.notifications.channels'))->columns(2)->schema([
                Select::make('primary_channel')->label(__('settings.notifications.primary_channel'))
                    ->options(['whatsapp' => __('communication.channels.whatsapp'), 'mail' => __('communication.channels.mail')])
                    ->required()->native(false)->helperText(__('settings.notifications.primary_channel_help')),
                Toggle::make('fallback_enabled')->label(__('settings.notifications.fallback'))
                    ->helperText(__('settings.notifications.fallback_help'))->inline(false),
                Toggle::make('mail_enabled')->label(__('settings.notifications.mail_enabled'))->inline(false),
            ]),
            Section::make(__('settings.notifications.timing'))->columns(2)->schema([
                TagsInput::make('reminder_hours')->label(__('settings.notifications.reminder_hours'))
                    ->helperText(__('settings.notifications.reminder_hours_help'))
                    ->nestedRecursiveRules(['integer', 'min:1', 'max:168'])->columnSpanFull(),
                TimePicker::make('quiet_start')->label(__('settings.notifications.quiet_start'))->seconds(false)->timezone('UTC')->required(),
                TimePicker::make('quiet_end')->label(__('settings.notifications.quiet_end'))->seconds(false)->timezone('UTC')->required()
                    ->helperText(__('settings.notifications.quiet_help')),
                TextInput::make('quote_reminder_days')->label(__('settings.notifications.quote_reminder_days'))
                    ->integer()->required()->minValue(0)->maxValue(60)->suffix(__('settings.units.days')),
                TextInput::make('review_request_days')->label(__('settings.notifications.review_request_days'))
                    ->helperText(__('settings.notifications.zero_off'))
                    ->integer()->required()->minValue(0)->maxValue(90)->suffix(__('settings.units.days')),
            ]),
            Section::make(__('settings.notifications.automatic'))->columns(2)->schema([
                Toggle::make('auto_send_checkin_report')->label(__('settings.notifications.auto_checkin')),
                Toggle::make('auto_send_delivery_report')->label(__('settings.notifications.auto_delivery')),
                TextInput::make('log_retention_days')->label(__('settings.notifications.log_retention_days'))
                    ->integer()->required()->minValue(7)->maxValue(1825)->suffix(__('settings.units.days')),
            ]),
        ]);
    }
}
