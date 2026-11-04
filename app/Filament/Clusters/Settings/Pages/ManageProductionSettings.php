<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\ProductionSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageProductionSettings extends SettingsPage
{
    protected static string $settings = ProductionSettings::class;

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'production';

    public static function getSectionGroup(): string
    {
        return 'workshop';
    }

    public static function getNavigationLabel(): string
    {
        return __('work_orders.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('work_orders.settings.alerts'))
                ->columns(2)
                ->schema([
                    TextInput::make('stalled_after_hours')->label(__('work_orders.settings.stalled_after_hours'))
                        ->integer()->required()->minValue(1)->maxValue(720)->suffix('h'),
                    TextInput::make('late_warning_hours')->label(__('work_orders.settings.late_warning_hours'))
                        ->integer()->required()->minValue(1)->maxValue(240)->suffix('h'),
                    TextInput::make('progress_photos_max')->label(__('work_orders.settings.progress_photos_max'))
                        ->integer()->required()->minValue(0)->maxValue(20),
                    Toggle::make('unconfirmed_appointment_alert')->label(__('work_orders.settings.unconfirmed_appointment_alert')),
                ]),
        ]);
    }
}
