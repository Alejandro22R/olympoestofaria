<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Resources\Pages\ListRecords;

class ListInspections extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'inspections';
    }

    protected static string $resource = InspectionResource::class;
}
