<?php

namespace App\Filament\Resources\ServiceTypes\Pages;

use App\Filament\Concerns\FillsTranslations;
use App\Filament\Resources\ServiceTypes\ServiceTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceType extends EditRecord
{
    use FillsTranslations;

    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
