<?php

namespace App\Domain\Inspections\Support;

use App\Models\Vehicle;
use App\Models\VehicleSilhouette;

/**
 * Siluetas del vehículo (vista superior en corte) con las zonas marcables del check-in.
 * Se leen de la tabla editable vehicle_silhouettes (Ajustes > Check-in) por tipo de carrocería;
 * si no hay ninguna, se usa la silueta sedán incorporada.
 */
final class VehicleSilhouettes
{
    public const DEFAULT = 'sedan';

    public const VIEWBOX = '0 0 200 400';

    public const OUTLINE = 'M60 18 Q100 4 140 18 Q170 30 172 70 L176 330 Q174 380 140 390 Q100 398 60 390 Q26 380 24 330 L28 70 Q30 30 60 18 Z';

    /** @var array<string, VehicleSilhouette|null> */
    private static array $cache = [];

    /**
     * Zonas de la silueta sedán incorporada: [x, y, ancho, alto, radio].
     *
     * @return array<string, array{x: float, y: float, w: float, h: float, rx: float}>
     */
    public static function defaultZones(): array
    {
        $z = fn (float $x, float $y, float $w, float $h, float $rx = 6) => compact('x', 'y', 'w', 'h', 'rx');

        return [
            'front_bumper' => $z(50, 12, 100, 16, 8),
            'hood' => $z(38, 32, 124, 62, 10),
            'windshield' => $z(42, 98, 116, 22, 6),
            'dashboard' => $z(46, 124, 108, 18, 4),
            'steering_wheel' => $z(56, 144, 30, 20, 10),
            'driver_seat' => $z(52, 168, 40, 50, 8),
            'passenger_seat' => $z(108, 168, 40, 50, 8),
            'center_console' => $z(94, 150, 12, 70, 3),
            'rear_seat' => $z(52, 232, 96, 52, 10),
            'floor' => $z(52, 220, 96, 10, 2),
            'left_front_door' => $z(26, 130, 18, 90, 4),
            'left_rear_door' => $z(26, 224, 18, 70, 4),
            'right_front_door' => $z(156, 130, 18, 90, 4),
            'right_rear_door' => $z(156, 224, 18, 70, 4),
            'rear_window' => $z(44, 290, 112, 18, 6),
            'trunk' => $z(38, 312, 124, 58, 10),
            'rear_bumper' => $z(50, 374, 100, 14, 8),
        ];
    }

    /**
     * Clave de silueta para un vehículo según el tipo de carrocería de su modelo de catálogo.
     */
    public static function forVehicle(Vehicle $vehicle): string
    {
        $body = $vehicle->vehicleModel?->body_type;

        return $body !== null && self::find($body) !== null ? $body : self::DEFAULT;
    }

    public static function find(string $silhouette): ?VehicleSilhouette
    {
        if (! array_key_exists($silhouette, self::$cache)) {
            self::$cache[$silhouette] = VehicleSilhouette::query()->where('body_type', $silhouette)->where('is_active', true)->first();
        }

        return self::$cache[$silhouette];
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    public static function viewBox(string $silhouette = self::DEFAULT): string
    {
        return self::find($silhouette)->view_box ?? self::VIEWBOX;
    }

    public static function outline(string $silhouette = self::DEFAULT): string
    {
        return self::find($silhouette)->outline ?? self::OUTLINE;
    }

    /**
     * Zonas: clave => forma SVG y punto central (en %) para marcas elegidas en la lista.
     *
     * @return array<string, array{shape: array{type: string, x: float, y: float, w: float, h: float, rx: float}, center: array{0: float, 1: float}}>
     */
    public static function zones(string $silhouette = self::DEFAULT): array
    {
        $raw = self::find($silhouette)->zones ?? self::defaultZones();
        [, , $vw, $vh] = array_map('floatval', explode(' ', self::viewBox($silhouette)));

        $zones = [];
        foreach ($raw as $key => $r) {
            $shape = ['type' => 'rect', 'x' => (float) $r['x'], 'y' => (float) $r['y'], 'w' => (float) $r['w'], 'h' => (float) $r['h'], 'rx' => (float) ($r['rx'] ?? 6)];
            $zones[$key] = [
                'shape' => $shape,
                'center' => [round(($shape['x'] + $shape['w'] / 2) / $vw * 100, 2), round(($shape['y'] + $shape['h'] / 2) / $vh * 100, 2)],
            ];
        }

        return $zones;
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
            ->mapWithKeys(fn (string $zone) => [$zone => self::label($zone)])
            ->all();
    }

    public static function label(string $zone): string
    {
        $key = 'inspections.zones.'.$zone;

        return __($key) === $key ? str($zone)->replace('_', ' ')->ucfirst()->toString() : __($key);
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
