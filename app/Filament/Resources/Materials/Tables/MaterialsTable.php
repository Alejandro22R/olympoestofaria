<?php

namespace App\Filament\Resources\Materials\Tables;

use App\Domain\Catalog\Enums\MaterialType;
use App\Filament\Support\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('variants'))
            ->columns([
                TextColumn::make('name')->label(__('catalog.fields.name'))->searchable(),
                TextColumn::make('type')->label(__('catalog.fields.type'))->badge(),
                TextColumn::make('supplier')->label(__('catalog.fields.supplier'))->toggleable(),
                TextColumn::make('price_per_m2_cents')->label(__('catalog.fields.price_per_m2'))
                    ->formatStateUsing(fn (int $state) => Money::format($state))->sortable(),
                TextColumn::make('variants_count')->label(__('catalog.fields.variants_count')),
                IconColumn::make('is_active')->label(__('catalog.fields.is_active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('catalog.fields.type'))->options(MaterialType::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
