<?php

namespace Database\Seeders;

use App\Domain\Catalog\Enums\MaterialType;
use App\Domain\Catalog\Enums\PricingUnit;
use App\Models\Material;
use App\Models\ServiceType;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

/**
 * Catálogo de demostración: servicios, materiales con variantes y 3 modelos de auto.
 * Precios en centavos (BRL) y solo de ejemplo.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['seats', ['pt_BR' => 'Bancos', 'es' => 'Asientos'], 180000, PricingUnit::Vehicle, 16, 6.5],
            ['steering_wheel', ['pt_BR' => 'Volante', 'es' => 'Volante'], 45000, PricingUnit::Piece, 4, 0.4],
            ['doors', ['pt_BR' => 'Forros de porta', 'es' => 'Puertas'], 60000, PricingUnit::Vehicle, 6, 1.6],
            ['headliner', ['pt_BR' => 'Teto', 'es' => 'Techo'], 70000, PricingUnit::Vehicle, 6, 4.0],
            ['flooring', ['pt_BR' => 'Carpete', 'es' => 'Piso'], 55000, PricingUnit::Vehicle, 5, 4.5],
            ['colortek', ['pt_BR' => 'Pintura Colortek', 'es' => 'Tinta Colortek'], 35000, PricingUnit::Piece, 3, null],
            ['restoration', ['pt_BR' => 'Restauração de clássicos', 'es' => 'Restauración de clásicos'], 500000, PricingUnit::Vehicle, 60, null],
        ];

        foreach ($services as $i => [$slug, $name, $price, $unit, $hours, $m2]) {
            ServiceType::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'base_price_cents' => $price,
                'pricing_unit' => $unit,
                'estimated_hours' => $hours,
                'material_m2' => $m2,
                'sort' => $i,
                'is_active' => true,
            ]);
        }

        $materials = [
            [MaterialType::Leather, ['pt_BR' => 'Couro natural Nappa', 'es' => 'Cuero natural Nappa'], 'Curtume Demo', 'Brasil', 18000, 32000, [
                [['pt_BR' => 'Preto', 'es' => 'Negro'], '#111111'],
                [['pt_BR' => 'Caramelo', 'es' => 'Caramelo'], '#B5651D'],
                [['pt_BR' => 'Laranja', 'es' => 'Naranja'], '#D9661F'],
                [['pt_BR' => 'Vermelho', 'es' => 'Rojo'], '#8E1B1B'],
            ]],
            [MaterialType::Vinyl, ['pt_BR' => 'Corino premium', 'es' => 'Vinilo premium'], 'Fornecedor Demo', 'Brasil', 6000, 12000, [
                [['pt_BR' => 'Preto', 'es' => 'Negro'], '#141414'],
                [['pt_BR' => 'Cinza', 'es' => 'Gris'], '#5A5A5A'],
                [['pt_BR' => 'Bege', 'es' => 'Beige'], '#CDB89A'],
            ]],
            [MaterialType::Fabric, ['pt_BR' => 'Alcântara', 'es' => 'Alcántara'], 'Importadora Demo', 'Itália', 22000, 39000, [
                [['pt_BR' => 'Grafite', 'es' => 'Grafito'], '#2E2E2E'],
                [['pt_BR' => 'Azul Olympo', 'es' => 'Azul Olympo'], '#0B5E8F'],
            ]],
            [MaterialType::Ink, ['pt_BR' => 'Tinta Colortek', 'es' => 'Tinta Colortek'], 'Colortek', 'Brasil', 9000, 16000, [
                [['pt_BR' => 'Preto fosco', 'es' => 'Negro mate'], '#1A1A1A'],
                [['pt_BR' => 'Marrom', 'es' => 'Marrón'], '#5C3A21'],
            ]],
        ];

        foreach ($materials as [$type, $name, $supplier, $origin, $cost, $price, $variants]) {
            $material = Material::query()->firstOrNew(['type' => $type->value, 'supplier' => $supplier]);
            $material->fill([
                'name' => $name,
                'origin' => $origin,
                'cost_per_m2_cents' => $cost,
                'price_per_m2_cents' => $price,
                'is_active' => true,
            ])->save();

            foreach ($variants as [$vName, $hex]) {
                $material->variants()->updateOrCreate(['color_hex' => $hex], ['name' => $vName, 'is_active' => true]);
            }
        }

        $zones = ['driver_seat', 'passenger_seat', 'rear_seat', 'steering_wheel', 'door_panels', 'headliner', 'floor'];

        foreach ([
            ['Volkswagen', 'Fusca', 1959, 1996, 'sedan'],
            ['Chevrolet', 'Onix', 2012, null, 'hatch'],
            ['Toyota', 'Hilux', 2005, null, 'pickup'],
        ] as [$brand, $model, $from, $to, $body]) {
            VehicleModel::query()->updateOrCreate(['brand' => $brand, 'model' => $model], [
                'year_from' => $from,
                'year_to' => $to,
                'body_type' => $body,
                'zones' => $zones,
                'is_active' => true,
            ]);
        }
    }
}
