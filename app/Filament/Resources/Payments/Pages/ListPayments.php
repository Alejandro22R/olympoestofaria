<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    #[On('echo-private:workshop,.payment.updated')]
    public function refreshPayments(): void
    {
        // Un pago confirmado por el webhook aparece al instante.
    }
}
