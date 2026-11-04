<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vehicle.customer', 'stage', 'responsible']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->formatStateUsing(fn (WorkOrder $record) => $record->number())->sortable(),
                TextColumn::make('vehicle.plate')->label(__('work_orders.fields.vehicle'))
                    ->formatStateUsing(fn (WorkOrder $record) => $record->vehicle->label())->searchable(),
                TextColumn::make('vehicle.customer.name')->label(__('work_orders.fields.customer'))->searchable(),
                TextColumn::make('stage.name')->label(__('work_orders.fields.stage'))->badge()
                    ->color(fn (WorkOrder $record) => Color::hex($record->stage->color_hex ?? '#737373')),
                TextColumn::make('status')->label(__('work_orders.fields.status'))->badge(),
                TextColumn::make('responsible.name')->label(__('work_orders.fields.responsible'))->placeholder('—')->toggleable(),
                TextColumn::make('due_at')->label(__('work_orders.fields.due_at'))->dateTime('d/m/Y H:i')->sortable()
                    ->color(fn (WorkOrder $record) => $record->isLate() ? 'danger' : null)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('work_orders.fields.status'))->options(WorkOrderStatus::class)->multiple()
                    ->default([WorkOrderStatus::Received->value, WorkOrderStatus::InProduction->value, WorkOrderStatus::Ready->value]),
                Filter::make('late')->label(__('work_orders.filters.late'))
                    ->query(fn (Builder $query) => $query->whereIn('status', WorkOrderStatus::onBoard())->where('due_at', '<', now())),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
