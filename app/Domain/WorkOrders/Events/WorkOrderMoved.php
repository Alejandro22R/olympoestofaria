<?php

namespace App\Domain\WorkOrders\Events;

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

    public function __construct(
        public int $workOrderId,
        public ?int $stageId,
        public string $status,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('workshop')];
    }

    public function broadcastAs(): string
    {
        return 'work-order.moved';
    }
}
