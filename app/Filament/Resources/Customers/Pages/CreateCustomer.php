<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Domain\Customers\Actions\SaveCustomer;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomer extends CreateRecord
{
    use MapsDomainValidation;

    protected static string $resource = CustomerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->withFormErrors(fn () => app(SaveCustomer::class)->execute($data));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
