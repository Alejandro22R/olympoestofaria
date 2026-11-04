<?php

namespace App\Filament\Resources\InventoryItems;

use App\Domain\Inventory\Actions\RecordInventoryMovement;
use App\Domain\Inventory\Enums\InventoryUnit;
use App\Filament\Resources\InventoryItems\Pages\CreateInventoryItem;
use App\Filament\Resources\InventoryItems\Pages\EditInventoryItem;
use App\Filament\Resources\InventoryItems\Pages\ListInventoryItems;
use App\Filament\Resources\InventoryItems\RelationManagers\MovementsRelationManager;
use App\Filament\Support\Money;
use App\Filament\Support\MoneyInput;
use App\Models\InventoryItem;
use App\Models\MaterialVariant;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 27;

    public static function getModelLabel(): string
    {
        return __('inventory.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('inventory.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.production');
    }

    public static function getNavigationBadge(): ?string
    {
        $low = InventoryItem::query()->belowMinimum()->count();

        return $low > 0 ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextInput::make('name')->label(__('inventory.fields.name'))->required()->maxLength(160)->columnSpan(2),
                TextInput::make('sku')->label(__('catalog.fields.sku'))->maxLength(60)->unique(ignoreRecord: true),
                Select::make('material_variant_id')->label(__('inventory.fields.material_variant'))
                    ->options(fn () => MaterialVariant::query()->with('material')->get()
                        ->mapWithKeys(fn (MaterialVariant $v) => [$v->id => $v->material->name.' — '.$v->name])->all())
                    ->searchable()->unique(ignoreRecord: true),
                Select::make('unit')->label(__('inventory.fields.unit'))->options(InventoryUnit::class)->required()->native(false),
                MoneyInput::make('unit_cost_cents')->label(__('inventory.fields.unit_cost')),
                Placeholder::make('stock_info')->label(__('inventory.fields.stock'))
                    ->content(fn (?InventoryItem $record) => $record ? RecordInventoryMovement::format($record->stock).' '.$record->unit->getLabel() : '0')
                    ->helperText(__('inventory.messages.stock_by_movements')),
                TextInput::make('min_stock')->label(__('inventory.fields.min_stock'))->numeric()->minValue(0)->required()->default(0),
                Toggle::make('is_active')->label(__('catalog.fields.is_active'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('inventory.fields.name'))->searchable()->sortable(),
                TextColumn::make('stock')->label(__('inventory.fields.stock'))
                    ->formatStateUsing(fn (InventoryItem $record) => RecordInventoryMovement::format($record->stock).' '.$record->unit->getLabel())
                    ->color(fn (InventoryItem $record) => $record->isBelowMinimum() ? 'danger' : null)
                    ->weight(fn (InventoryItem $record) => $record->isBelowMinimum() ? 'bold' : null)
                    ->sortable(),
                TextColumn::make('min_stock')->label(__('inventory.fields.min_stock'))
                    ->formatStateUsing(fn (InventoryItem $record) => RecordInventoryMovement::format($record->min_stock)),
                TextColumn::make('unit_cost_cents')->label(__('inventory.fields.unit_cost'))
                    ->formatStateUsing(fn (int $state) => Money::format($state))->toggleable(),
            ])
            ->filters([
                Filter::make('low')->label(__('inventory.filters.low'))
                    ->query(fn (Builder $query) => $query->whereColumn('stock', '<=', 'min_stock')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [MovementsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryItems::route('/'),
            'create' => CreateInventoryItem::route('/create'),
            'edit' => EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
