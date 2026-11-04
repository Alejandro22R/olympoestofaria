<?php

namespace App\Filament\Resources\VehicleSilhouettes\Pages;

use App\Filament\Resources\VehicleSilhouettes\VehicleSilhouetteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleSilhouettes extends ManageRecords
{
    protected static string $resource = VehicleSilhouetteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
