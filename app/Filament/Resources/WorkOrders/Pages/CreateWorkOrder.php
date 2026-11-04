<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Domain\WorkOrders\Actions\CreateWorkOrder as CreateWorkOrderAction;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWorkOrder extends CreateRecord
{
    use MapsDomainValidation;

    protected static string $resource = WorkOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Los ítems los guarda el Repeater (relationship) después de crear la orden.
        return $this->withFormErrors(fn () => app(CreateWorkOrderAction::class)->execute($data, null, auth()->user()));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
