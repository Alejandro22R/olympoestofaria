<?php

namespace App\Domain\WorkOrders\Events;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Una orden cambió de etapa/estado: los tableros abiertos se actualizan al instante.
 * Solo viajan identificadores y estado.
 */
class WorkOrderMoved implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public ?int $customerId;

    public function __construct(
        public int $workOrderId,
        public ?int $stageId,
        public string $status,
    ) {
        $this->customerId = WorkOrder::query()->whereKey($workOrderId)->with('vehicle')->first()?->vehicle?->customer_id;
    }

    /**
     * Taller y, además, el canal privado del cliente dueño (seguimiento en vivo).
     *
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_values(array_filter([
            new PrivateChannel('workshop'),
            $this->customerId !== null ? new PrivateChannel('customers.'.$this->customerId) : null,
        ]));
    }

    public function broadcastAs(): string
    {
        return 'work-order.moved';
    }
}
