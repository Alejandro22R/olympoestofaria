<?php

namespace Database\Seeders;

use App\Domain\Inventory\Enums\InventoryUnit;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\InventoryItem;
use App\Models\MaterialVariant;
use App\Models\ServiceType;
use App\Models\WarrantyTerm;
use App\Models\WorkflowStage;
use Illuminate\Database\Seeder;

/**
 * Etapas del tablero, plazos de garantía e inventario inicial (valores de ejemplo editables).
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            [WorkOrderStatus::Received, 'Recepção', 'Recepción', '#737373', true, false],
            [WorkOrderStatus::InProduction, 'Desmontagem', 'Desmontaje', '#0877B5', true, false],
            [WorkOrderStatus::InProduction, 'Corte', 'Corte', '#0B8FD9', true, false],
            [WorkOrderStatus::InProduction, 'Costura', 'Costura', '#0B8FD9', true, true],
            [WorkOrderStatus::InProduction, 'Montagem', 'Montaje', '#066A9F', true, false],
            [WorkOrderStatus::Ready, 'Pronto para entrega', 'Listo para entrega', '#2FA36B', true, true],
        ];
        foreach ($stages as $i => [$status, $pt, $es, $color, $visible, $notifies]) {
            WorkflowStage::query()->updateOrCreate(['sort' => $i, 'status' => $status->value], [
                'name' => ['pt_BR' => $pt, 'es' => $es],
                'color_hex' => $color,
                'visible_to_customer' => $visible,
                'notifies_customer' => $notifies,
                'is_active' => true,
            ]);
        }

        foreach (['seats' => 12, 'steering_wheel' => 6, 'doors' => 6, 'headliner' => 6, 'flooring' => 6, 'colortek' => 3, 'restoration' => 12] as $slug => $months) {
            if ($service = ServiceType::query()->where('slug', $slug)->first()) {
                WarrantyTerm::query()->updateOrCreate(['service_type_id' => $service->id], ['months' => $months]);
            }
        }

        foreach (MaterialVariant::query()->with('material')->get() as $variant) {
            InventoryItem::query()->firstOrCreate(['material_variant_id' => $variant->id], [
                'name' => $variant->material->getTranslation('name', 'pt_BR').' — '.$variant->getTranslation('name', 'pt_BR'),
                'unit' => $variant->material->type->value === 'ink' ? InventoryUnit::Liter : InventoryUnit::SquareMeter,
                'min_stock' => 5,
                'unit_cost_cents' => $variant->material->cost_per_m2_cents,
            ]);
        }

        foreach ([['Linha de costura nylon', InventoryUnit::Unit, 10, 1500], ['Espuma D33 (placa)', InventoryUnit::Unit, 4, 9000], ['Cola de contato', InventoryUnit::Liter, 2, 4500]] as [$name, $unit, $min, $cost]) {
            InventoryItem::query()->firstOrCreate(['name' => $name], ['unit' => $unit, 'min_stock' => $min, 'unit_cost_cents' => $cost]);
        }
    }
}
