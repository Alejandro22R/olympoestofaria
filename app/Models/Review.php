<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Reseña del cliente tras la entrega. El cliente da nota, comentario y permiso para publicar;
 * el taller elige las fotos de antes/después y la publica en la landing (moderación).
 */
#[Fillable(['work_order_id', 'customer_id', 'rating', 'comment', 'is_published', 'consent_publish', 'requested_at'])]
class Review extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
            'consent_publish' => 'boolean',
            'requested_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function registerMediaCollections(): void
    {
        foreach (['before', 'after'] as $collection) {
            $this->addMediaCollection($collection)->singleFile()
                ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections('before', 'after')->format('webp')->fit(Fit::Crop, 640, 480);
    }

    /**
     * Visibles en la landing: el cliente lo permitió y el taller la publicó.
     *
     * @param  Builder<Review>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->where('consent_publish', true)->latest('updated_at');
    }

    /** Nombre para mostrar: nombre y la inicial del apellido (privacidad). */
    public function displayName(): string
    {
        $parts = preg_split('/\s+/', trim($this->customer->name)) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1).'.' : '';

        return trim($first.' '.$last);
    }
}
