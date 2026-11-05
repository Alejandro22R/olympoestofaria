<?php

namespace App\Filament\Resources\GalleryItems\Pages;

use App\Filament\Resources\GalleryItems\GalleryItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryItem extends CreateRecord
{
    use QueuesVideoProcessing;

    protected static string $resource = GalleryItemResource::class;

    protected function afterCreate(): void
    {
        $this->queueVideoIfNeeded();
    }
}
