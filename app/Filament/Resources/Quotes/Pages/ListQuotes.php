<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'quotes';
    }

    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
