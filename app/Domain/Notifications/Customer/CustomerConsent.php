<?php

namespace App\Domain\Notifications\Customer;

use App\Models\Customer;

/**
 * Reglas de consentimiento: los transaccionales siempre salen (por WhatsApp solo con opt-in, regla de Meta);
 * los de avance respetan la preferencia; los comerciales exigen opt-in y no haberse dado de baja.
 */
final class CustomerConsent
{
    public static function allows(Customer $customer, MessageEvent $event): bool
    {
        $prefs = $customer->notification_preferences ?? [];

        return match ($event->category()) {
            'transactional' => true,
            'updates' => $customer->unsubscribed_at === null && ($prefs['updates'] ?? true),
            default => $customer->unsubscribed_at === null && $customer->contact_opt_in_at !== null && ($prefs['marketing'] ?? true),
        };
    }
}
