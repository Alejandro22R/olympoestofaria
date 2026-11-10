<?php

namespace App\Filament\Resources\Materials\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\Materials\MaterialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterials extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'catalog';
    }

    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
