<?php

namespace App\Filament\Resources\Holidays;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Holidays\Pages\ManageHolidays;
use App\Models\Holiday;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * Ajustes > Taller > Feriados y cierres (lista editable, no JSON dentro de ajustes).
 */
class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('holidays.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('holidays.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('holidays.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.workshop');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')->label(__('holidays.fields.date'))
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, callable $get) => $rule->where('branch_id', $get('branch_id'))),
                TextInput::make('name')->label(__('holidays.fields.name'))->required()->maxLength(120),
                TextInput::make('capacity')->label(__('holidays.fields.capacity'))
                    ->helperText(__('holidays.fields.capacity_help'))
                    ->integer()->minValue(0)->maxValue(100),
                Select::make('branch_id')->label(__('holidays.fields.branch'))
                    ->relationship('branch', 'name')
                    ->placeholder(__('holidays.fields.all_branches')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->label(__('holidays.fields.date'))->date('D d/m/Y')->sortable(),
                TextColumn::make('name')->label(__('holidays.fields.name'))->searchable(),
                TextColumn::make('capacity')->label(__('holidays.fields.capacity'))
                    ->placeholder(__('appointments.closed')),
                TextColumn::make('branch.name')->label(__('holidays.fields.branch'))
                    ->placeholder(__('holidays.fields.all_branches'))->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHolidays::route('/'),
        ];
    }
}
