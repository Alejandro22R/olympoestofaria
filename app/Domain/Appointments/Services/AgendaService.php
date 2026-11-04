<?php

namespace App\Domain\Appointments\Services;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Settings\AgendaSettings;
use App\Domain\Settings\GeneralSettings;
use App\Models\Appointment;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Reglas de capacidad de la agenda. Todos los valores salen de los Ajustes
 * (AgendaSettings / GeneralSettings) y de la tabla de feriados.
 */
class AgendaService
{
    public function __construct(
        private readonly AgendaSettings $agenda,
        private readonly GeneralSettings $general,
    ) {}

    public function timezone(): string
    {
        return $this->general->timezone;
    }

    /**
     * Fecha local del taller. Un string 'Y-m-d' se interpreta como día local;
     * un instante (Carbon) se convierte a la zona horaria del taller.
     */
    public function localDate(CarbonInterface|string $day): CarbonImmutable
    {
        if (is_string($day)) {
            return CarbonImmutable::parse($day, $this->timezone())->startOfDay();
        }

        return CarbonImmutable::instance($day)->setTimezone($this->timezone())->startOfDay();
    }

    public function isWorkingDay(CarbonInterface|string $date, ?int $branchId = null): bool
    {
        return $this->capacityFor($date, $branchId) > 0;
    }

    public function holidayOn(CarbonInterface|string $date, ?int $branchId = null): ?Holiday
    {
        return Holiday::query()
            ->whereDate('date', $this->localDate($date)->toDateString())
            ->where(fn ($q) => $q->whereNull('branch_id')->when($branchId, fn ($q) => $q->orWhere('branch_id', $branchId)))
            ->first();
    }

    /**
     * Capacidad del día (vehículos que se pueden recibir).
     */
    public function capacityFor(CarbonInterface|string $date, ?int $branchId = null): int
    {
        $local = $this->localDate($date);

        if (! in_array($local->dayOfWeekIso, $this->agenda->working_days, true)) {
            return 0;
        }

        $holiday = $this->holidayOn($local, $branchId);
        if ($holiday !== null) {
            return $holiday->capacity ?? 0;
        }

        return $local->dayOfWeekIso === CarbonInterface::SATURDAY
            ? $this->agenda->vehicles_per_saturday
            : $this->agenda->vehicles_per_day;
    }

    public function bookedOn(CarbonInterface|string $date, ?int $branchId = null, ?int $ignoreAppointmentId = null): int
    {
        $start = $this->localDate($date);
        $end = $start->addDay();

        return Appointment::query()
            ->whereIn('status', AppointmentStatus::occupying())
            ->where('scheduled_at', '>=', $start->utc())
            ->where('scheduled_at', '<', $end->utc())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($ignoreAppointmentId, fn ($q) => $q->whereKeyNot($ignoreAppointmentId))
            ->count();
    }

    public function remainingOn(CarbonInterface|string $date, ?int $branchId = null, ?int $ignoreAppointmentId = null): int
    {
        return max(0, $this->capacityFor($date, $branchId) - $this->bookedOn($date, $branchId, $ignoreAppointmentId));
    }

    public function isWithinBusinessHours(CarbonInterface $moment): bool
    {
        $local = CarbonImmutable::instance($moment)->setTimezone($this->timezone());
        $time = $local->format('H:i');

        return $time >= $this->agenda->opens_at && $time < $this->agenda->closes_at;
    }

    public function earliestAllowed(): CarbonImmutable
    {
        return CarbonImmutable::now()->addHours($this->agenda->min_advance_hours);
    }

    public function blocksWhenFull(): bool
    {
        return $this->agenda->block_when_full;
    }

    /**
     * Resumen de ocupación por día para el calendario.
     *
     * @return array<string, array{capacity: int, booked: int, holiday: ?string}>
     */
    public function occupancyForRange(CarbonInterface|string $from, CarbonInterface|string $to, ?int $branchId = null): array
    {
        $tz = $this->timezone();
        $start = $this->localDate($from);
        $end = $this->localDate($to);

        $counts = Appointment::query()
            ->whereIn('status', AppointmentStatus::occupying())
            ->where('scheduled_at', '>=', $start->utc())
            ->where('scheduled_at', '<', $end->addDay()->utc())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['scheduled_at'])
            ->countBy(fn (Appointment $a) => $a->scheduled_at->setTimezone($tz)->toDateString());

        $holidays = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($q) => $q->whereNull('branch_id')->when($branchId, fn ($q) => $q->orWhere('branch_id', $branchId)))
            ->get()
            ->keyBy(fn (Holiday $h) => $h->date->toDateString());

        $result = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $key = $day->toDateString();
            $result[$key] = [
                'capacity' => $this->capacityFor($day, $branchId),
                'booked' => (int) ($counts[$key] ?? 0),
                'holiday' => $holidays->get($key)?->name,
            ];
        }

        return $result;
    }
}
