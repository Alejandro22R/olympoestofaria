<?php

namespace App\Filament\Resources\WorkOrders\RelationManagers;

use App\Models\WorkOrderUpdate;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Historial de la orden: cambios de etapa, notas y fotos de avance (solo lectura).
 */
class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('work_orders.sections.history');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['stage', 'user']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('inspections.fields.created_at'))->dateTime('d/m/Y H:i'),
                TextColumn::make('stage.name')->label(__('work_orders.fields.stage'))->placeholder('—'),
                TextColumn::make('status')->label(__('work_orders.fields.status'))->badge(),
                TextColumn::make('note')->label(__('work_orders.fields.note'))->wrap()->placeholder('—'),
                TextColumn::make('photos')->label(__('work_orders.fields.photos'))
                    ->state(fn (WorkOrderUpdate $record) => count($record->photos)),
                IconColumn::make('visible_to_customer')->label(__('work_orders.fields.visible_to_customer'))->boolean(),
                TextColumn::make('user.name')->label(__('work_orders.fields.user'))->placeholder('—'),
            ]);
    }
}
