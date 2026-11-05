<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\AlertSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ajustes > Comunicación > Alertas automáticas: umbrales comerciales, escalamiento al dueño y
 * alertas técnicas. (Los umbrales de producción y stock están en Ajustes > Producción.)
 */
class ManageAlertSettings extends SettingsPage
{
    protected static string $settings = AlertSettings::class;

    protected static ?int $navigationSort = 34;

    protected static ?string $slug = 'alerts';

    public static function getSectionGroup(): string
    {
        return 'communication';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.alerts.title');
    }

    public function form(Schema $schema): Schema
    {
        $hours = __('settings.units.hours');
        $days = __('settings.units.days');

        return $schema->components([
            Section::make(__('settings.alerts.commercial'))->columns(2)->schema([
                TextInput::make('lead_unanswered_hours')->label(__('settings.alerts.lead_unanswered_hours'))->integer()->required()->minValue(1)->maxValue(720)->suffix($hours),
                TextInput::make('quote_unanswered_days')->label(__('settings.alerts.quote_unanswered_days'))->integer()->required()->minValue(1)->maxValue(60)->suffix($days),
                TextInput::make('deposit_overdue_hours')->label(__('settings.alerts.deposit_overdue_hours'))->integer()->required()->minValue(1)->maxValue(720)->suffix($hours),
                TextInput::make('balance_overdue_days')->label(__('settings.alerts.balance_overdue_days'))->integer()->required()->minValue(1)->maxValue(90)->suffix($days),
            ]),
            Section::make(__('settings.alerts.escalation'))->columns(2)->schema([
                TextInput::make('escalation_hours')->label(__('settings.alerts.escalation_hours'))
                    ->helperText(__('settings.alerts.escalation_help'))
                    ->integer()->required()->minValue(0)->maxValue(168)->suffix($hours),
                Toggle::make('escalate_by_mail')->label(__('settings.alerts.escalate_by_mail'))->inline(false),
            ]),
            Section::make(__('settings.alerts.technical'))->columns(2)->schema([
                TextInput::make('failed_jobs_threshold')->label(__('settings.alerts.failed_jobs_threshold'))->integer()->required()->minValue(1)->maxValue(1000),
                TextInput::make('disk_free_percent')->label(__('settings.alerts.disk_free_percent'))->integer()->required()->minValue(1)->maxValue(50)->suffix('%'),
            ]),
        ]);
    }
}
