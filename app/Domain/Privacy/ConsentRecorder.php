<?php

namespace App\Domain\Privacy;

use App\Models\ConsentEvent;
use App\Models\Customer;

/**
 * Registro de consentimientos (LGPD art. 8: la prueba del consentimiento es del responsable).
 * Guarda tipo, si se otorgó o retiró, el origen, quién lo registró y un hash de la IP (no la IP).
 */
class ConsentRecorder
{
    public const PRIVACY = 'privacy';

    public const CONTACT = 'contact';

    public const DOCUMENT = 'document';

    public function record(Customer $customer, string $type, bool $granted, string $source): ConsentEvent
    {
        $ip = request()->ip();

        return ConsentEvent::query()->create([
            'customer_id' => $customer->id,
            'type' => $type,
            'granted' => $granted,
            'source' => $source,
            'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
            'user_id' => auth()->user()?->isStaff() ? auth()->id() : null,
            'created_at' => now(),
        ]);
    }
}
