<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Evento de consentimiento LGPD (solo se agrega, nunca se edita).
 *
 * @property Carbon $created_at
 */
#[Fillable(['customer_id', 'type', 'granted', 'source', 'ip_hash', 'user_id', 'created_at'])]
class ConsentEvent extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['granted' => 'boolean', 'created_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
