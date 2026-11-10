<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Concerns\HasModuleIntro;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppointments extends ListRecords
{
    use HasModuleIntro;

    protected static function moduleKey(): string
    {
        return 'appointments';
    }

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
