<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'reviews';
    }

    protected static string $resource = ReviewResource::class;
}
