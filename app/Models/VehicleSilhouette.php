<?php

namespace App\Models;

use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Silueta por carrocería con sus zonas marcables (Ajustes > Check-in).
 *
 * @property array<string, array{x: float, y: float, w: float, h: float, rx?: float}> $zones
 */
#[Fillable(['body_type', 'name', 'view_box', 'outline', 'zones', 'is_active'])]
class VehicleSilhouette extends Model implements Translatable
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['zones' => 'array', 'is_active' => 'boolean'];
    }
}
