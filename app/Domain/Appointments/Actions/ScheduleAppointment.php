<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Appointments\Events\AppointmentChanged;
use App\Domain\Appointments\Services\AgendaService;
use App\Domain\Auth\Permission;
use App\Domain\Notifications\StaffNotifier;
use App\Filament\Pages\AppointmentCalendar;
use App\Models\Appointment;
use App\Notifications\StaffAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Crea o reprograma una cita aplicando las reglas de negocio de la agenda:
 * no en el pasado (con anticipación mínima), día laborable y sin feriado,
 * dentro del horario, entrega posterior a la recepción y capacidad del día.
 */
class ScheduleAppointment
{
    public function __construct(
        private readonly AgendaService $agenda,
        private readonly StaffNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Appointment $appointment = null): Appointment
    {
        $appointment ??= new Appointment;
        $appointment->fill($data);
        $appointment->status ??= AppointmentStatus::Scheduled;

        $isNew = ! $appointment->exists;
        $moved = $appointment->exists && $appointment->isDirty('scheduled_at');

        $appointment = DB::transaction(function () use ($appointment): Appointment {
            $this->lockDay($appointment);
            $this->validate($appointment);
            $appointment->save();

            return $appointment;
        });

        if ($isNew || $moved) {
            $this->announce($appointment, $isNew ? 'created' : 'moved');
        }

        return $appointment;
    }

    /**
     * @throws ValidationException
     */
    public function validate(Appointment $appointment): void
    {
        $scheduledAt = CarbonImmutable::instance($appointment->scheduled_at);
        $errors = [];

        // Las reglas de agenda se aplican al crear o reprogramar; editar notas de una cita existente no las reevalúa.
        $needsAgendaCheck = ! $appointment->exists || $appointment->isDirty('scheduled_at');

        if ($needsAgendaCheck) {
            if ($scheduledAt->lt($this->agenda->earliestAllowed())) {
                $errors['scheduled_at'][] = __('appointments.errors.too_soon');
            }

            if (! $this->agenda->isWorkingDay($scheduledAt, $appointment->branch_id)) {
                $holiday = $this->agenda->holidayOn($scheduledAt, $appointment->branch_id);
                $errors['scheduled_at'][] = $holiday
                    ? __('appointments.errors.holiday', ['name' => $holiday->name])
                    : __('appointments.errors.non_working_day');
            } elseif (! $this->agenda->isWithinBusinessHours($scheduledAt)) {
                $errors['scheduled_at'][] = __('appointments.errors.outside_hours');
            } elseif (
                in_array($appointment->status, AppointmentStatus::occupying(), true)
                && $this->agenda->blocksWhenFull()
                && $this->agenda->remainingOn($scheduledAt, $appointment->branch_id, $appointment->getKey()) < 1
            ) {
                $errors['scheduled_at'][] = __('appointments.errors.day_full');
            }
        }

        if ($appointment->promised_delivery_at !== null && $appointment->promised_delivery_at->lt($scheduledAt)) {
            $errors['promised_delivery_at'][] = __('appointments.errors.delivery_before_reception');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Aviso en vivo al calendario y a la campana del personal (menos a quien hizo el cambio).
     */
    private function announce(Appointment $appointment, string $change): void
    {
        AppointmentChanged::dispatch($appointment->id, $change);

        $tz = $this->agenda->timezone();
        $this->notifier->notify(Permission::AppointmentsManage, new StaffAlert(
            type: 'appointment_'.$change,
            titleKey: 'notifications.appointment_'.$change,
            params: [
                'plate' => $appointment->vehicle->formattedPlate(),
                'date' => $appointment->scheduled_at->setTimezone($tz)->format('d/m H:i'),
            ],
            bodyKey: 'notifications.appointment_when',
            url: AppointmentCalendar::getUrl(['month' => $appointment->scheduled_at->setTimezone($tz)->format('Y-m')]),
            color: 'info',
            icon: 'heroicon-o-calendar-days',
        ), except: auth()->user());
    }

    /**
     * Serializa las reservas del mismo día para evitar dobles reservas concurrentes.
     */
    private function lockDay(Appointment $appointment): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $day = $this->agenda->localDate($appointment->scheduled_at)->format('Ymd');
        DB::select('SELECT pg_advisory_xact_lock(?, ?)', [(int) ($appointment->branch_id ?? 0), (int) $day]);
    }
}
