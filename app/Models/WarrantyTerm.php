<?php

namespace App\Models;

use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Plazo de garantía por tipo de servicio (Ajustes > Garantías).
 */
#[Fillable(['service_type_id', 'months', 'conditions'])]
class WarrantyTerm extends Model implements Translatable
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['conditions'];

    protected function casts(): array
    {
        return ['months' => 'integer'];
    }

    /**
     * @return BelongsTo<ServiceType, $this>
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
