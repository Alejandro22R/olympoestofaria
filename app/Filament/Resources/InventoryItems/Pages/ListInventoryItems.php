<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryItems extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'inventory';
    }

    protected static string $resource = InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
