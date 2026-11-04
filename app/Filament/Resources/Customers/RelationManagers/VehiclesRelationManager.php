<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Vehicles\Schemas\VehicleForm;
use App\Filament\Resources\Vehicles\Tables\VehiclesTable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('vehicles.plural');
    }

    public static function getModelLabel(): string
    {
        return __('vehicles.model');
    }

    public function form(Schema $schema): Schema
    {
        return VehicleForm::configure($schema, withCustomer: false);
    }

    public function table(Table $table): Table
    {
        return VehiclesTable::configure($table, withCustomer: false)
            ->recordTitleAttribute('plate')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
