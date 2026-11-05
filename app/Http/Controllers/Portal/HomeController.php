<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\PortalSettings;
use App\Models\GalleryItem;
use App\Models\Material;
use App\Models\Review;
use App\Models\ServiceType;
use App\Models\VehicleModel;
use App\Models\WorkflowStage;
use Illuminate\Contracts\View\View;

/**
 * Landing de impacto (PLAN.md §2.2). Todo el contenido llega en el HTML (SEO);
 * la animación (GSAP/Lenis) solo lo mejora y se carga únicamente en esta página.
 */
class HomeController
{
    public function __invoke(PortalSettings $portal, GeneralSettings $general): View
    {
        $gallery = GalleryItem::query()->published()->with(['media', 'serviceType', 'material'])->get();

        return view('portal.home', [
            'portal' => $portal,
            'general' => $general,
            'hero' => $gallery->first(fn (GalleryItem $i) => in_array('hero', $i->placements, true) && $i->hasVideo()),
            'services' => ServiceType::query()->where('is_active', true)->orderBy('sort')->get(),
            'serviceMedia' => $gallery->filter(fn (GalleryItem $i) => in_array('services', $i->placements, true))->keyBy('service_type_id'),
            'galleryItems' => $gallery->filter(fn (GalleryItem $i) => in_array('gallery', $i->placements, true))->values(),
            'beforeAfter' => $gallery->first(fn (GalleryItem $i) => in_array('before_after', $i->placements, true) && $i->hasMedia('before') && $i->hasMedia('image')),
            'reels' => $gallery->filter(fn (GalleryItem $i) => in_array('reels', $i->placements, true) && $i->hasVideo())->values(),
            'materials' => Material::query()->where('is_active', true)->with(['variants' => fn ($q) => $q->where('is_active', true)])->get(),
            'brands' => VehicleModel::query()->where('is_active', true)->distinct()->orderBy('brand')->pluck('brand'),
            'stages' => WorkflowStage::query()->ordered()->where('visible_to_customer', true)->get(),
            'reviews' => Review::query()->published()->with(['customer', 'media'])->limit(6)->get(),
        ]);
    }
}
