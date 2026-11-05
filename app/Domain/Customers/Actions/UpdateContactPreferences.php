<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Privacy\ConsentRecorder;
use App\Models\Customer;

/**
 * Preferencias de contacto del cliente: avances del trabajo y mensajes de marketing
 * (los transaccionales siempre se envían). La baja apaga ambos.
 */
class UpdateContactPreferences
{
    public function unsubscribe(Customer $customer): void
    {
        if ($customer->unsubscribed_at === null) {
            $customer->forceFill(['unsubscribed_at' => now()])->save();
            activity()->performedOn($customer)->event('unsubscribed')->log('unsubscribed');
            app(ConsentRecorder::class)->record($customer, ConsentRecorder::CONTACT, false, 'email_link');
        }
    }

    public function update(Customer $customer, bool $updates, bool $marketing): void
    {
        $customer->forceFill([
            'notification_preferences' => ['updates' => $updates, 'marketing' => $marketing],
            'unsubscribed_at' => $updates || $marketing ? null : ($customer->unsubscribed_at ?? now()),
        ])->save();
    }
}
