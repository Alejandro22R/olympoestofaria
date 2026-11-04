<?php

namespace Database\Seeders;

use App\Domain\Inventory\Actions\RecordInventoryMovement;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\WorkOrders\Actions\CreateWorkOrder;
use App\Domain\WorkOrders\Actions\MoveWorkOrder;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\InventoryItem;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkflowStage;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

/**
 * Demo de producción (solo desarrollo): stock inicial y órdenes repartidas en el tablero.
 */
class DemoProductionSeeder extends Seeder
{
    public function run(RecordInventoryMovement $record, CreateWorkOrder $create, MoveWorkOrder $move): void
    {
        if (WorkOrder::query()->exists()) {
            return;
        }

        $owner = User::query()->where('email', 'owner@olympo.test')->first();

        foreach (InventoryItem::query()->where('stock', 0)->get() as $i => $item) {
            $record->execute($item, MovementType::In, $i % 5 === 0 ? 4 : 25, $owner, null, 'Estoque inicial (demo)');
        }

        $seats = ServiceType::query()->where('slug', 'seats')->first();
        $wheel = ServiceType::query()->where('slug', 'steering_wheel')->first();
        $leather = InventoryItem::query()->whereNotNull('material_variant_id')->where('stock', '>', 10)->first();
        $stages = WorkflowStage::query()->ordered()->get();

        foreach (Vehicle::query()->limit(3)->get() as $n => $vehicle) {
            $order = $create->execute([
                'vehicle_id' => $vehicle->id,
                'due_at' => now()->addDays(2 + $n * 3),
                'responsible_id' => User::query()->where('email', 'upholsterer@olympo.test')->value('id'),
            ], null, $owner);

            $order->items()->create(['service_type_id' => $seats?->id, 'description' => 'Bancos em couro', 'inventory_item_id' => $leather?->id, 'quantity' => 6.5, 'hours' => 16, 'price_cents' => 280000]);
            $order->items()->create(['service_type_id' => $wheel?->id, 'description' => 'Volante costurado', 'hours' => 4, 'price_cents' => 45000]);

            $target = $stages->where('status', WorkOrderStatus::InProduction)->values()[$n] ?? null;
            if ($n > 0 && $target !== null) {
                $move->execute($order, $target, $owner, 'Início da produção');
            }
        }
    }
}
