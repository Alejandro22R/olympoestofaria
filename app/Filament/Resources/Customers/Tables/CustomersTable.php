<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('vehicles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('customers.fields.name'))->searchable()->sortable(),
                TextColumn::make('phone')->label(__('customers.fields.phone'))->searchable(),
                TextColumn::make('email')->label(__('customers.fields.email'))->searchable()->toggleable(),
                TextColumn::make('vehicles_count')->label(__('customers.fields.vehicles_count'))->sortable(),
                IconColumn::make('lgpd_consent_at')->label('LGPD')
                    ->boolean()->state(fn ($record) => $record->lgpd_consent_at !== null),
                TextColumn::make('created_at')->label(__('customers.fields.created_at'))
                    ->dateTime('d/m/Y')->sortable()->toggleable(),
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
