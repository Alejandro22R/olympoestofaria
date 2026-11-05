<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quote_id', 'service_type_id', 'material_variant_id', 'description', 'quantity', 'unit_price_cents', 'total_cents', 'sort'])]
class QuoteItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return BelongsTo<ServiceType, $this>
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * @return BelongsTo<MaterialVariant, $this>
     */
    public function materialVariant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class);
    }
}
