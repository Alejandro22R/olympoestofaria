<?php

namespace Database\Factories;

use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Vehicle;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'status' => WorkOrderStatus::Received,
            'due_at' => now()->addDays(5),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (WorkOrder $order): void {
            if ($order->workflow_stage_id === null) {
                $order->workflow_stage_id = WorkflowStage::firstFor($order->status)?->id;
                $order->stage_changed_at = now();
            }
        });
    }

    public function inStatus(WorkOrderStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
