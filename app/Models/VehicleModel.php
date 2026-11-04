<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de modelos de auto con plantilla SVG de interior (zonas tapizables).
 */
#[Fillable(['brand', 'model', 'year_from', 'year_to', 'body_type', 'interior_svg_path', 'zones', 'is_active'])]
class VehicleModel extends Model
{
    protected function casts(): array
    {
        return [
            'zones' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function label(): string
    {
        $years = $this->year_from ? " ({$this->year_from}–".($this->year_to ?? '…').')' : '';

        return "{$this->brand} {$this->model}{$years}";
    }
}
