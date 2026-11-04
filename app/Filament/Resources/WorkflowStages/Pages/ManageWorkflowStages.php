<?php

namespace App\Filament\Resources\WorkflowStages\Pages;

use App\Filament\Resources\WorkflowStages\WorkflowStageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWorkflowStages extends ManageRecords
{
    protected static string $resource = WorkflowStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
