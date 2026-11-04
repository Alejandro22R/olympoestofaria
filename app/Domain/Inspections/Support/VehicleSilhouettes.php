<?php

namespace App\Domain\Inspections\Support;

/**
 * Siluetas del vehículo (vista superior en corte) con las zonas marcables del check-in.
 * Coordenadas en un viewBox de 200 × 400. En la Fase 3 pasarán a la tabla editable vehicle_silhouettes.
 */
final class VehicleSilhouettes
{
    public const DEFAULT = 'sedan';

    public const VIEWBOX = '0 0 200 400';

    /**
     * Contorno de la carrocería.
     */
    public static function outline(string $silhouette = self::DEFAULT): string
    {
        return 'M60 18 Q100 4 140 18 Q170 30 172 70 L176 330 Q174 380 140 390 Q100 398 60 390 Q26 380 24 330 L28 70 Q30 30 60 18 Z';
    }

    /**
     * Zonas: clave => [forma SVG (rect x,y,w,h | path d), punto central para marcas manuales].
     *
     * @return array<string, array{shape: array{type: string, x?: float, y?: float, w?: float, h?: float, rx?: float, d?: string}, center: array{0: float, 1: float}}>
     */
    public static function zones(string $silhouette = self::DEFAULT): array
    {
        $rect = fn (float $x, float $y, float $w, float $h, float $rx = 6) => [
            'shape' => ['type' => 'rect', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'rx' => $rx],
            'center' => [round(($x + $w / 2) / 2, 2), round(($y + $h / 2) / 4, 2)],
        ];

        return [
            'front_bumper' => $rect(50, 12, 100, 16, 8),
            'hood' => $rect(38, 32, 124, 62, 10),
            'windshield' => $rect(42, 98, 116, 22, 6),
            'dashboard' => $rect(46, 124, 108, 18, 4),
            'steering_wheel' => $rect(56, 144, 30, 20, 10),
            'driver_seat' => $rect(52, 168, 40, 50, 8),
            'passenger_seat' => $rect(108, 168, 40, 50, 8),
            'center_console' => $rect(94, 150, 12, 70, 3),
            'rear_seat' => $rect(52, 232, 96, 52, 10),
            'floor' => $rect(52, 220, 96, 10, 2),
            'left_front_door' => $rect(26, 130, 18, 90, 4),
            'left_rear_door' => $rect(26, 224, 18, 70, 4),
            'right_front_door' => $rect(156, 130, 18, 90, 4),
            'right_rear_door' => $rect(156, 224, 18, 70, 4),
            'rear_window' => $rect(44, 290, 112, 18, 6),
            'trunk' => $rect(38, 312, 124, 58, 10),
            'rear_bumper' => $rect(50, 374, 100, 14, 8),
        ];
    }

    /**
     * Zonas que no se ven en la vista superior y se eligen en la lista (punto en el centro).
     *
     * @return list<string>
     */
    public static function extraZones(): array
    {
        return ['headliner', 'other'];
    }

    /**
     * @return array<string, string>
     */
    public static function options(string $silhouette = self::DEFAULT): array
    {
        return collect([...array_keys(self::zones($silhouette)), ...self::extraZones()])
            ->mapWithKeys(fn (string $zone) => [$zone => __('inspections.zones.'.$zone)])
            ->all();
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function centerOf(string $zone, string $silhouette = self::DEFAULT): array
    {
        return self::zones($silhouette)[$zone]['center'] ?? [50.0, 50.0];
    }

    public static function exists(string $zone, string $silhouette = self::DEFAULT): bool
    {
        return array_key_exists($zone, self::options($silhouette));
    }
}
