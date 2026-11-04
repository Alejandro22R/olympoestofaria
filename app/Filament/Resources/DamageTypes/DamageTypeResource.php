<?php

namespace App\Filament\Resources\DamageTypes;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\DamageTypes\Pages\ManageDamageTypes;
use App\Models\DamageType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Ajustes > Check-in > Tipos de daño (lista editable).
 */
class DamageTypeResource extends Resource
{
    protected static ?string $model = DamageType::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('inspections.settings.damage_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inspections.settings.damage_types');
    }

    public static function getNavigationLabel(): string
    {
        return __('inspections.settings.damage_types');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('settings.groups.workshop');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->label(__('catalog.fields.slug'))->required()->alphaDash()->maxLength(40)->unique(ignoreRecord: true),
            TextInput::make('name.pt_BR')->label(__('catalog.fields.name_pt'))->required()->maxLength(60),
            TextInput::make('name.es')->label(__('catalog.fields.name_es'))->maxLength(60),
            ColorPicker::make('color_hex')->label(__('catalog.fields.color_hex'))->hex()->required()->regex('/^#[0-9A-Fa-f]{6}$/'),
            TextInput::make('sort')->label(__('catalog.fields.sort'))->integer()->default(0),
            Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ColorColumn::make('color_hex')->label(__('catalog.fields.color_hex')),
                TextColumn::make('name')->label(__('catalog.fields.name')),
                IconColumn::make('is_active')->label(__('catalog.fields.is_active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, DamageType $record) => [...$data, 'name' => $record->getTranslations('name')]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDamageTypes::route('/')];
    }
}
