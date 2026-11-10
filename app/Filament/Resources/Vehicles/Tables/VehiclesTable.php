<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Filament\Support\CardList;
use App\Models\Vehicle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table, bool $withCustomer = true): Table
    {
        return CardList::grid($table, 'vehicles')
            ->defaultSort('created_at', 'desc')
            ->columns([
                CardList::card(
                    TextColumn::make('brand')->label(__('vehicles.fields.brand'))->searchable(['brand', 'model'])->sortable()
                        ->formatStateUsing(fn (Vehicle $record) => trim($record->brand.' '.$record->model.' '.$record->year)),
                    TextColumn::make('plate')->label(__('vehicles.fields.plate'))
                        ->formatStateUsing(fn (Vehicle $record) => $record->formattedPlate())->searchable()->sortable()->color('gray'),
                    array_values(array_filter([
                        $withCustomer ? CardList::detail(TextColumn::make('customer.name')->label(__('vehicles.fields.customer'))->searchable()->sortable(), 'heroicon-m-user') : null,
                        CardList::detail(TextColumn::make('color')->label(__('vehicles.fields.color'))->placeholder('—'), 'heroicon-m-swatch'),
                        CardList::detail(TextColumn::make('mileage_km')->label(__('vehicles.fields.mileage_km'))
                            ->numeric(thousandsSeparator: '.')->suffix(' km')->placeholder('—'), 'heroicon-m-arrow-trending-up'),
                    ])),
                ),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
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
