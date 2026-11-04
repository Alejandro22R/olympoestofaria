<?php

namespace Database\Seeders;

use App\Domain\Inspections\Support\VehicleSilhouettes;
use App\Models\VehicleSilhouette;
use Illuminate\Database\Seeder;

/**
 * Siluetas iniciales por carrocería (editables en Ajustes > Check-in > Siluetas).
 */
class SilhouetteSeeder extends Seeder
{
    public function run(): void
    {
        $sedan = VehicleSilhouettes::defaultZones();

        $hatch = $sedan;
        $hatch['rear_window'] = ['x' => 44, 'y' => 290, 'w' => 112, 'h' => 22, 'rx' => 6];
        $hatch['trunk'] = ['x' => 40, 'y' => 316, 'w' => 120, 'h' => 40, 'rx' => 10];
        $hatch['rear_bumper'] = ['x' => 50, 'y' => 360, 'w' => 100, 'h' => 14, 'rx' => 8];

        $pickup = $sedan;
        unset($pickup['trunk'], $pickup['rear_seat'], $pickup['left_rear_door'], $pickup['right_rear_door']);
        $pickup['rear_window'] = ['x' => 44, 'y' => 226, 'w' => 112, 'h' => 14, 'rx' => 4];
        $pickup['cargo_bed'] = ['x' => 34, 'y' => 248, 'w' => 132, 'h' => 120, 'rx' => 6];

        $silhouettes = [
            'sedan' => [['pt_BR' => 'Sedã', 'es' => 'Sedán'], VehicleSilhouettes::OUTLINE, $sedan],
            'hatch' => [['pt_BR' => 'Hatch', 'es' => 'Hatchback'], 'M60 18 Q100 4 140 18 Q170 30 172 70 L176 330 Q174 372 140 378 Q100 384 60 378 Q26 372 24 330 L28 70 Q30 30 60 18 Z', $hatch],
            'pickup' => [['pt_BR' => 'Picape', 'es' => 'Camioneta'], 'M60 18 Q100 4 140 18 Q170 30 172 70 L172 380 Q172 392 160 392 L40 392 Q28 392 28 380 L28 70 Q30 30 60 18 Z', $pickup],
        ];

        foreach ($silhouettes as $body => [$name, $outline, $zones]) {
            VehicleSilhouette::query()->updateOrCreate(['body_type' => $body], [
                'name' => $name,
                'view_box' => VehicleSilhouettes::VIEWBOX,
                'outline' => $outline,
                'zones' => $zones,
                'is_active' => true,
            ]);
        }

        VehicleSilhouettes::flush();
    }
}
