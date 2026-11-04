<?php

namespace App\Filament\Resources\WarrantyTerms\Pages;

use App\Filament\Resources\WarrantyTerms\WarrantyTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWarrantyTerms extends ManageRecords
{
    protected static string $resource = WarrantyTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
