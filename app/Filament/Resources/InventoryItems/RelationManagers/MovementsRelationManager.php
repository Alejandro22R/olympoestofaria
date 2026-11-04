<?php

namespace App\Filament\Resources\InventoryItems\RelationManagers;

use App\Domain\Inventory\Actions\RecordInventoryMovement;
use App\Models\InventoryMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('inventory.movements');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'workOrder']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('inspections.fields.created_at'))->dateTime('d/m/Y H:i'),
                TextColumn::make('type')->label(__('inventory.fields.type'))->badge(),
                TextColumn::make('quantity')->label(__('inventory.fields.quantity'))
                    ->formatStateUsing(fn (InventoryMovement $record) => ($record->quantity > 0 ? '+' : '').RecordInventoryMovement::format($record->quantity)),
                TextColumn::make('stock_after')->label(__('inventory.fields.stock_after'))
                    ->formatStateUsing(fn (InventoryMovement $record) => RecordInventoryMovement::format($record->stock_after)),
                TextColumn::make('workOrder.id')->label(__('work_orders.model'))
                    ->formatStateUsing(fn (InventoryMovement $record) => $record->workOrder?->number())->placeholder('—'),
                TextColumn::make('note')->label(__('inventory.fields.note'))->placeholder('—'),
                TextColumn::make('user.name')->label(__('work_orders.fields.user'))->placeholder('—'),
            ]);
    }
}
