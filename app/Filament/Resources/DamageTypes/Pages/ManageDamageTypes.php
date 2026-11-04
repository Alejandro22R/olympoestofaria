<?php

namespace App\Filament\Resources\DamageTypes\Pages;

use App\Filament\Resources\DamageTypes\DamageTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDamageTypes extends ManageRecords
{
    protected static string $resource = DamageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
