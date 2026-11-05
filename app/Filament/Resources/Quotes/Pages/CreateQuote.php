<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Domain\Quotes\Actions\CreateQuote as CreateQuoteAction;
use App\Domain\Quotes\Actions\SaveQuote;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateQuote extends CreateRecord
{
    use MapsDomainValidation;

    protected static string $resource = QuoteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->withFormErrors(function () use ($data) {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $quote = app(CreateQuoteAction::class)->blank($customer, auth()->user(), [
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'valid_until' => $data['valid_until'],
            ]);

            return app(SaveQuote::class)->execute($quote, $data, auth()->user());
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
