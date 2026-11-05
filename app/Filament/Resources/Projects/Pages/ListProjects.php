<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\On;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    #[On('echo-private:workshop,.lead.created')]
    public function refreshLeads(): void
    {
        // Una nueva solicitud aparece al instante en la lista.
    }
}
