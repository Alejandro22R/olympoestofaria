<?php

namespace App\Domain\Payments\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Cambio de estado de un pago: la pantalla del cliente y el panel se actualizan sin recargar.
 * Solo viajan identificadores y estado.
 */
class PaymentUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $paymentId, public int $customerId, public string $status) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('customers.'.$this->customerId), new PrivateChannel('workshop')];
    }

    public function broadcastAs(): string
    {
        return 'payment.updated';
    }
}
