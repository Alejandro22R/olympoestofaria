<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Pages\ProductionBoard;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')->label(__('work_orders.board'))->icon(Heroicon::OutlinedViewColumns)->color('gray')
                ->url(ProductionBoard::getUrl()),
            CreateAction::make(),
        ];
    }
}
