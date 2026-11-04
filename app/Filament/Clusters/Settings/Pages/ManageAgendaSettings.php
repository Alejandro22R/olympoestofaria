<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Domain\Settings\AgendaSettings;
use App\Filament\Clusters\Settings\SettingsPage;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ManageAgendaSettings extends SettingsPage
{
    protected static string $settings = AgendaSettings::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'agenda';

    public static function getSectionGroup(): string
    {
        return 'workshop';
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.sections.agenda');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['working_days'] = array_values(array_map('intval', $data['working_days'] ?? []));
        $data['opens_at'] = substr((string) $data['opens_at'], 0, 5);
        $data['closes_at'] = substr((string) $data['closes_at'], 0, 5);

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        $days = collect(range(1, 7))->mapWithKeys(fn (int $d) => [$d => __('settings.weekdays.'.$d)])->all();

        return $schema->components([
            Section::make(__('settings.agenda.hours'))
                ->columns(2)
                ->schema([
                    CheckboxList::make('working_days')->label(__('settings.agenda.working_days'))
                        ->options($days)->columns(4)->required()->minItems(1)->columnSpanFull(),
                    TimePicker::make('opens_at')->label(__('settings.agenda.opens_at'))->seconds(false)->timezone('UTC')->required(),
                    TimePicker::make('closes_at')->label(__('settings.agenda.closes_at'))->seconds(false)->timezone('UTC')->required()
                        ->after('opens_at'),
                ]),
            Section::make(__('settings.agenda.capacity'))
                ->columns(2)
                ->schema([
                    TextInput::make('vehicles_per_day')->label(__('settings.agenda.vehicles_per_day'))
                        ->integer()->required()->minValue(0)->maxValue(100),
                    TextInput::make('vehicles_per_saturday')->label(__('settings.agenda.vehicles_per_saturday'))
                        ->integer()->required()->minValue(0)->maxValue(100)
                        ->visible(fn (Get $get) => in_array(6, array_map('intval', (array) $get('working_days')), true)),
                    TextInput::make('min_advance_hours')->label(__('settings.agenda.min_advance_hours'))
                        ->integer()->required()->minValue(0)->maxValue(720)->suffix('h'),
                    TextInput::make('reception_minutes')->label(__('settings.agenda.reception_minutes'))
                        ->integer()->required()->minValue(5)->maxValue(480)->suffix('min'),
                    Toggle::make('block_when_full')->label(__('settings.agenda.block_when_full'))
                        ->helperText(__('settings.agenda.block_when_full_help'))->columnSpanFull(),
                ]),
        ]);
    }
}
