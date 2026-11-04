<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\WarrantySettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageWarrantySettings extends SettingsPage
{
    protected static string $settings = WarrantySettings::class;

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'warranty';

    public static function getSectionGroup(): string
    {
        return 'workshop';
    }

    public static function getNavigationLabel(): string
    {
        return __('deliveries.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('deliveries.settings.title'))
                ->columns(2)
                ->schema([
                    TextInput::make('default_months')->label(__('deliveries.settings.default_months'))
                        ->helperText(__('deliveries.settings.default_months_help'))
                        ->integer()->required()->minValue(0)->maxValue(120),
                    TextInput::make('review_reminder_days')->label(__('deliveries.settings.review_reminder_days'))
                        ->integer()->required()->minValue(0)->maxValue(365)->suffix('d'),
                    Textarea::make('conditions.pt_BR')->label(__('deliveries.settings.conditions').' (PT)')->required()->rows(4)->maxLength(2000),
                    Textarea::make('conditions.es')->label(__('deliveries.settings.conditions').' (ES)')->required()->rows(4)->maxLength(2000),
                ]),
        ]);
    }
}
