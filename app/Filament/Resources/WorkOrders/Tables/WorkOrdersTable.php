<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Support\CardList;
use App\Models\WorkOrder;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return CardList::grid($table, 'work_orders')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vehicle.customer', 'stage', 'responsible']))
            ->defaultSort('id', 'desc')
            ->columns([
                CardList::card(
                    TextColumn::make('vehicle.plate')->label(__('work_orders.fields.vehicle'))
                        ->formatStateUsing(fn (WorkOrder $record) => $record->vehicle->label())->searchable(),
                    TextColumn::make('stage.name')->label(__('work_orders.fields.stage'))
                        ->color(fn (WorkOrder $record) => Color::hex($record->stage->color_hex ?? '#737373')),
                    [
                        CardList::detail(TextColumn::make('id')->label('#')->formatStateUsing(fn (WorkOrder $record) => $record->number().' · '.$record->status->getLabel())->sortable(), 'heroicon-m-hashtag'),
                        CardList::detail(TextColumn::make('vehicle.customer.name')->label(__('work_orders.fields.customer'))->searchable(), 'heroicon-m-user'),
                        CardList::detail(TextColumn::make('due_at')->label(__('work_orders.fields.due_at'))->sortable()
                            ->formatStateUsing(fn (WorkOrder $record) => __('work_orders.cards.due', ['date' => $record->due_at?->setTimezone(FilamentTimezone::get())->format('d/m H:i')]))
                            ->placeholder(__('work_orders.cards.no_due')), 'heroicon-m-clock')
                            ->color(fn (WorkOrder $record) => $record->isLate() ? 'danger' : 'gray'),
                        CardList::detail(TextColumn::make('responsible.name')->label(__('work_orders.fields.responsible'))->placeholder(__('work_orders.cards.no_responsible')), 'heroicon-m-wrench'),
                    ],
                ),
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
