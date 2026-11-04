<?php

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Appointments\Services\AgendaService;
use App\Domain\Settings\AgendaSettings;
use App\Models\Appointment;
use App\Models\Holiday;

beforeEach(function () {
    freezeMonday();
    $this->agenda = fn () => app(AgendaService::class);
});

it('reads the daily capacity from the agenda settings', function (string $day, int $capacity) {
    expect(($this->agenda)()->capacityFor($day))->toBe($capacity);
})->with([
    'monday' => ['2026-11-09', 4],
    'friday' => ['2026-11-13', 4],
    'saturday' => ['2026-11-14', 2],
    'sunday (closed)' => ['2026-11-15', 0],
]);

it('closes the day on a holiday', function () {
    Holiday::create(['date' => '2026-11-10', 'name' => 'Feriado']);

    expect(($this->agenda)()->capacityFor('2026-11-10'))->toBe(0)
        ->and(($this->agenda)()->isWorkingDay('2026-11-10'))->toBeFalse();
});

it('uses the special capacity of a holiday when set', function () {
    Holiday::create(['date' => '2026-11-10', 'name' => 'Meio expediente', 'capacity' => 1]);

    expect(($this->agenda)()->capacityFor('2026-11-10'))->toBe(1);
});

it('counts only appointments that occupy capacity', function () {
    Appointment::factory()->create(['scheduled_at' => local('2026-11-10 09:00')]);
    Appointment::factory()->create(['scheduled_at' => local('2026-11-10 10:00'), 'status' => AppointmentStatus::Confirmed]);
    Appointment::factory()->create(['scheduled_at' => local('2026-11-10 11:00'), 'status' => AppointmentStatus::Cancelled]);
    Appointment::factory()->create(['scheduled_at' => local('2026-11-10 12:00'), 'status' => AppointmentStatus::NoShow]);

    expect(($this->agenda)()->bookedOn('2026-11-10'))->toBe(2)
        ->and(($this->agenda)()->remainingOn('2026-11-10'))->toBe(2);
});

it('groups appointments by the workshop local day, not UTC', function () {
    // 22:30 en São Paulo = 01:30 UTC del día siguiente.
    Appointment::factory()->create(['scheduled_at' => local('2026-11-10 22:30')]);

    expect(($this->agenda)()->bookedOn('2026-11-10'))->toBe(1)
        ->and(($this->agenda)()->bookedOn('2026-11-11'))->toBe(0);
});

it('picks up new settings values immediately', function () {
    $settings = app(AgendaSettings::class);
    $settings->vehicles_per_day = 7;
    $settings->save();

    expect(($this->agenda)()->capacityFor('2026-11-09'))->toBe(7);
});

it('builds the monthly occupancy grid', function () {
    Appointment::factory()->count(2)->create(['scheduled_at' => local('2026-11-11 09:00')]);
    Holiday::create(['date' => '2026-11-20', 'name' => 'Consciência Negra']);

    $grid = ($this->agenda)()->occupancyForRange('2026-11-09', '2026-11-22');

    expect($grid)->toHaveCount(14)
        ->and($grid['2026-11-11'])->toBe(['capacity' => 4, 'booked' => 2, 'holiday' => null])
        ->and($grid['2026-11-20'])->toBe(['capacity' => 0, 'booked' => 0, 'holiday' => 'Consciência Negra'])
        ->and($grid['2026-11-15']['capacity'])->toBe(0);
});
