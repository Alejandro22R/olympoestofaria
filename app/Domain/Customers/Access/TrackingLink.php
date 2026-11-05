<?php

namespace App\Domain\Customers\Access;

use App\Models\WorkOrder;
use Illuminate\Support\Facades\URL;

/**
 * Enlace firmado de seguimiento para el cliente (abre su sesión en el portal).
 */
class TrackingLink
{
    public const DAYS = 60;

    public function url(WorkOrder $order): string
    {
        return URL::temporarySignedRoute('portal.tracking', now()->addDays(self::DAYS), ['workOrder' => $order->id]);
    }

    public function whatsappUrl(WorkOrder $order): string
    {
        $customer = $order->vehicle->customer;
        $text = trans('tracking.whatsapp', [
            'name' => $customer->name,
            'plate' => $order->vehicle->formattedPlate(),
            'url' => $this->url($order),
        ], $customer->locale);

        return 'https://wa.me/'.ltrim($customer->phone, '+').'?text='.rawurlencode($text);
    }
}
