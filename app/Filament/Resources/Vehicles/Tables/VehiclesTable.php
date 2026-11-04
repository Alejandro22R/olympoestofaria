<?php

namespace App\Filament\Resources\Vehicles\Tables;

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
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(array_values(array_filter([
                TextColumn::make('plate')->label(__('vehicles.fields.plate'))
                    ->formatStateUsing(fn (Vehicle $record) => $record->formattedPlate())
                    ->searchable()->sortable()->weight('bold'),
                $withCustomer ? TextColumn::make('customer.name')->label(__('vehicles.fields.customer'))->searchable()->sortable() : null,
                TextColumn::make('brand')->label(__('vehicles.fields.brand'))->searchable()->sortable(),
                TextColumn::make('model')->label(__('vehicles.fields.model'))->searchable(),
                TextColumn::make('year')->label(__('vehicles.fields.year'))->sortable(),
                TextColumn::make('color')->label(__('vehicles.fields.color'))->toggleable(),
                TextColumn::make('mileage_km')->label(__('vehicles.fields.mileage_km'))
                    ->numeric(thousandsSeparator: '.')->suffix(' km')->toggleable(),
            ])))
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
