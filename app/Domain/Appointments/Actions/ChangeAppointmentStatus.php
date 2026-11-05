<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Appointments\Events\AppointmentChanged;
use App\Domain\Notifications\Customer\CustomerEvents;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

/**
 * Máquina de estados de la cita: solo permite las transiciones definidas en AppointmentStatus.
 * Cada cambio queda auditado por el activity log del modelo.
 */
class ChangeAppointmentStatus
{
    public function execute(Appointment $appointment, AppointmentStatus $to): Appointment
    {
        $from = $appointment->getOriginal('status');
        $from = $from instanceof AppointmentStatus ? $from : AppointmentStatus::from((string) $from);

        if ($from === $to) {
            return $appointment;
        }

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => __('appointments.errors.invalid_transition', ['from' => $from->getLabel(), 'to' => $to->getLabel()]),
            ]);
        }

        $appointment->status = $to;
        $appointment->save();

        AppointmentChanged::dispatch($appointment->id, 'status');
        if ($to === AppointmentStatus::Cancelled) {
            app(CustomerEvents::class)->appointment($appointment, MessageEvent::AppointmentCancelled, 'cancelled');
        }

        return $appointment;
    }
}
