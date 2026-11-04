<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

/**
 * Edición de una cita: valida la transición de estado y, si cambia la fecha, las reglas de agenda.
 */
class UpdateAppointment
{
    public function __construct(
        private readonly ScheduleAppointment $schedule,
        private readonly ChangeAppointmentStatus $changeStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Appointment $appointment, array $data): Appointment
    {
        $status = $data['status'] ?? null;
        unset($data['status']);

        return DB::transaction(function () use ($appointment, $data, $status): Appointment {
            $appointment = $this->schedule->execute($data, $appointment);

            if ($status !== null) {
                $to = $status instanceof AppointmentStatus ? $status : AppointmentStatus::from((string) $status);
                $appointment = $this->changeStatus->execute($appointment, $to);
            }

            return $appointment;
        });
    }
}
