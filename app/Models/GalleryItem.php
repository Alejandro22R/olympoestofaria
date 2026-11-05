<?php

namespace App\Models;

use App\Models\Contracts\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Pieza de la biblioteca de medios del portal (trabajos del taller).
 * Imágenes con conversiones WebP y srcset (medialibrary); video procesado por FFmpeg
 * a MP4 + WebM + póster (ProcessGalleryVideo).
 *
 * @property list<string> $placements
 */
#[Fillable(['title', 'service_type_id', 'material_id', 'brand', 'model', 'color', 'placements', 'is_published', 'sort'])]
class GalleryItem extends Model implements HasMedia, Translatable
{
    use HasTranslations, InteractsWithMedia;

    public const PLACEMENTS = ['hero', 'gallery', 'before_after', 'services', 'reels'];

    /** @var list<string> */
    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'placements' => 'array',
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
        $this->addMediaCollection('before')->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('video')->singleFile()
            ->acceptsMimeTypes(['video/mp4', 'video/quicktime', 'video/webm']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections('image', 'before')->format('webp')->fit(Fit::Crop, 480, 360);
        $this->addMediaConversion('large')->performOnCollections('image', 'before')->withResponsiveImages()->format('webp')->fit(Fit::Max, 1600, 1600);
    }

    /**
     * @param  Builder<GalleryItem>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort')->orderByDesc('id');
    }

    /**
     * @param  Builder<GalleryItem>  $query
     */
    public function scopePlacedIn(Builder $query, string $placement): void
    {
        $query->whereJsonContains('placements', $placement);
    }

    /**
     * Hay un video subido que todavía no se convirtió (o se reemplazó).
     */
    public function needsVideoProcessing(): bool
    {
        $media = $this->getFirstMedia('video');
        if ($media === null) {
            return false;
        }

        return $this->video_status !== 'processing'
            && ! str_contains((string) $this->video_mp4_path, 'clip-'.$media->id.'.');
    }

    public function hasVideo(): bool
    {
        return $this->video_status === 'ready' && $this->video_mp4_path !== null;
    }

    public function videoUrl(string $format = 'mp4'): ?string
    {
        $path = $format === 'webm' ? $this->video_webm_path : $this->video_mp4_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function posterUrl(): ?string
    {
        return $this->video_poster_path ? Storage::disk('public')->url($this->video_poster_path) : null;
    }

    /**
     * @return BelongsTo<ServiceType, $this>
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
