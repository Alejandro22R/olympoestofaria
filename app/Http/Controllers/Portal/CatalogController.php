<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Catalog\Enums\MaterialType;
use App\Domain\Settings\PortalSettings;
use App\Models\GalleryItem;
use App\Models\Material;
use Illuminate\Contracts\View\View;

/**
 * Catálogo público: materiales con sus colores y galería de trabajos filtrable.
 */
class CatalogController
{
    public function __invoke(PortalSettings $portal): View
    {
        return view('portal.catalog', [
            'portal' => $portal,
            'types' => MaterialType::cases(),
            'materials' => Material::query()->where('is_active', true)
                ->with(['variants' => fn ($q) => $q->where('is_active', true)])->orderBy('type')->get(),
            'galleryItems' => GalleryItem::query()->published()->placedIn('gallery')->with(['media', 'serviceType', 'material'])->get(),
        ]);
    }
}
