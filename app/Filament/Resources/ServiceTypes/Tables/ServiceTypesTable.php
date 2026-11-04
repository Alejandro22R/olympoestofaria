<?php

namespace App\Filament\Resources\ServiceTypes\Tables;

use App\Filament\Support\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label(__('catalog.fields.name'))->searchable(),
                TextColumn::make('base_price_cents')->label(__('catalog.fields.base_price'))
                    ->formatStateUsing(fn (int $state) => Money::format($state))->sortable(),
                TextColumn::make('pricing_unit')->label(__('catalog.fields.pricing_unit'))->badge(),
                TextColumn::make('estimated_hours')->label(__('catalog.fields.estimated_hours'))->toggleable(),
                IconColumn::make('is_active')->label(__('catalog.fields.is_active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
