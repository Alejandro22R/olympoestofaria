<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Customers\Actions\UpdateContactPreferences;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Baja de avisos no obligatorios (enlace firmado del correo). El GET solo muestra la confirmación
 * para que los antivirus de correo que abren enlaces no den de baja a nadie.
 */
class UnsubscribeController
{
    public function show(Customer $customer): View
    {
        return view('portal.unsubscribe', ['customer' => $customer, 'done' => false]);
    }

    public function store(Request $request, Customer $customer, UpdateContactPreferences $preferences): View
    {
        $preferences->unsubscribe($customer);

        return view('portal.unsubscribe', ['customer' => $customer, 'done' => true]);
    }
}
