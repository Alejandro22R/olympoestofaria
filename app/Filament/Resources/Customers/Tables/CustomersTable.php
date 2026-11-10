<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Support\CardList;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return CardList::grid($table, 'customers')
            ->modifyQueryUsing(fn ($query) => $query->withCount('vehicles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                CardList::card(
                    TextColumn::make('name')->label(__('customers.fields.name'))->searchable()->sortable(),
                    TextColumn::make('vehicles_count')->label(__('customers.fields.vehicles_count'))->sortable()
                        ->formatStateUsing(fn (int $state) => trans_choice('customers.cards.vehicles', $state, ['count' => $state]))->color('gray'),
                    [
                        CardList::detail(TextColumn::make('phone')->label(__('customers.fields.phone'))->searchable(), 'heroicon-m-phone'),
                        CardList::detail(TextColumn::make('email')->label(__('customers.fields.email'))->searchable()->placeholder(__('customers.cards.no_email')), 'heroicon-m-envelope'),
                        CardList::detail(TextColumn::make('created_at')->label(__('customers.fields.created_at'))->sortable()
                            ->formatStateUsing(fn ($state) => __('customers.cards.since', ['date' => $state?->format('d/m/Y')])), 'heroicon-m-calendar'),
                    ],
                ),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
