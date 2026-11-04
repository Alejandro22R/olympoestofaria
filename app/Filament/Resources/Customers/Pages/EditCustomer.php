<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Domain\Customers\Actions\SaveCustomer;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCustomer extends EditRecord
{
    use MapsDomainValidation;

    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  Customer  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Documento en blanco = conservar el actual.
        if (blank($data['document_number'] ?? null)) {
            unset($data['document_number']);
            if ($record->document_number !== null) {
                unset($data['document_type']);
            }
        }

        return $this->withFormErrors(fn () => app(SaveCustomer::class)->execute($data, $record));
    }
}
