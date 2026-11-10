<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'customers';
    }

    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
