<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Domain\Appointments\Actions\ScheduleAppointment;
use App\Domain\Settings\AgendaSettings;
use App\Filament\Concerns\MapsDomainValidation;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Branch;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAppointment extends CreateRecord
{
    use MapsDomainValidation;

    protected static string $resource = AppointmentResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();

        // Desde el calendario: ?date=YYYY-MM-DD precarga la hora de apertura de ese día.
        $date = request()->query('date');
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->form->fill([
                ...$this->form->getRawState(),
                'scheduled_at' => $date.' '.app(AgendaSettings::class)->opens_at.':00',
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = auth()->id();
        $data['branch_id'] = Branch::default()?->id;

        return $this->withFormErrors(fn () => app(ScheduleAppointment::class)->execute($data));
    }
}
