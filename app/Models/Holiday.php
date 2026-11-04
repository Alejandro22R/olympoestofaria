<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Feriado o cierre especial. capacity null/0 = cerrado; > 0 = capacidad especial ese día.
 */
/**
 * @property Carbon $date
 * @property int|null $capacity
 */
#[Fillable(['branch_id', 'date', 'name', 'capacity'])]
class Holiday extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
