<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\CheckinSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageCheckinSettings extends SettingsPage
{
    protected static string $settings = CheckinSettings::class;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'checkin';

    public static function getSectionGroup(): string
    {
        return 'workshop';
    }

    public static function getNavigationLabel(): string
    {
        return __('inspections.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inspections.settings.rules'))
                ->columns(2)
                ->schema([
                    Toggle::make('mileage_required')->label(__('inspections.settings.mileage_required'))->columnSpanFull(),
                    TextInput::make('max_photos_per_mark')->label(__('inspections.settings.max_photos_per_mark'))
                        ->integer()->required()->minValue(0)->maxValue(20),
                    TextInput::make('max_marks')->label(__('inspections.settings.max_marks'))
                        ->integer()->required()->minValue(1)->maxValue(100),
                    TextInput::make('report_link_days')->label(__('inspections.settings.report_link_days'))
                        ->integer()->required()->minValue(1)->maxValue(90)->suffix('d'),
                ]),
            Section::make(__('inspections.settings.texts'))
                ->columns(2)
                ->schema([
                    Textarea::make('legal_text.pt_BR')->label(__('inspections.settings.legal_text').' (PT)')->required()->rows(4)->maxLength(2000),
                    Textarea::make('legal_text.es')->label(__('inspections.settings.legal_text').' (ES)')->required()->rows(4)->maxLength(2000),
                    Textarea::make('consent_text.pt_BR')->label(__('inspections.settings.consent_text').' (PT)')->required()->rows(3)->maxLength(1000),
                    Textarea::make('consent_text.es')->label(__('inspections.settings.consent_text').' (ES)')->required()->rows(3)->maxLength(1000),
                ]),
        ]);
    }
}
