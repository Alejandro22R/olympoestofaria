<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Domain\Appointments\Actions\UpdateAppointment;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAppointment extends EditRecord
{
    use MapsDomainValidation;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  Appointment  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->withFormErrors(fn () => app(UpdateAppointment::class)->execute($record, $data));
    }
}
