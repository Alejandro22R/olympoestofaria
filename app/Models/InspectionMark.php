<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string> $photos Rutas en el disco privado.
 */
#[Fillable(['inspection_id', 'zone', 'x', 'y', 'damage_type_id', 'note', 'photos'])]
class InspectionMark extends Model
{
    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'photos' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * @return BelongsTo<DamageType, $this>
     */
    public function damageType(): BelongsTo
    {
        return $this->belongsTo(DamageType::class);
    }
}
