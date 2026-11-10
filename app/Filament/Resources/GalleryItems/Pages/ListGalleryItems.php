<?php

namespace App\Filament\Resources\GalleryItems\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\GalleryItems\GalleryItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGalleryItems extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'gallery';
    }

    protected static string $resource = GalleryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
