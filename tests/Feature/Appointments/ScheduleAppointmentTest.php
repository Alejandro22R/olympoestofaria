<?php

use App\Domain\Appointments\Actions\ScheduleAppointment;
use App\Domain\Appointments\Actions\UpdateAppointment;
use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Settings\AgendaSettings;
use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    freezeMonday();
    $this->vehicle = Vehicle::factory()->create();
    $this->schedule = fn (array $data, ?Appointment $a = null) => app(ScheduleAppointment::class)->execute([
        'vehicle_id' => $this->vehicle->id,
        ...$data,
    ], $a);
});

function scheduleErrors(callable $callback): array
{
    try {
        $callback();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return [];
}

it('schedules a valid appointment', function () {
    $appointment = ($this->schedule)(['scheduled_at' => local('2026-11-10 09:00')]);

    expect($appointment->exists)->toBeTrue()
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled);
});

it('rejects invalid reception dates', function (string $when, string $message) {
    $errors = scheduleErrors(fn () => ($this->schedule)(['scheduled_at' => local($when)]));

    expect($errors['scheduled_at'][0] ?? null)->toBe(__($message));
})->with([
    'in the past' => ['2026-11-06 10:00', 'appointments.errors.too_soon'],
    'before min advance' => ['2026-11-09 09:00', 'appointments.errors.too_soon'],
    'on sunday' => ['2026-11-15 10:00', 'appointments.errors.non_working_day'],
    'before opening' => ['2026-11-10 07:00', 'appointments.errors.outside_hours'],
    'at closing time' => ['2026-11-10 18:00', 'appointments.errors.outside_hours'],
]);

it('rejects a holiday with its name', function () {
    Holiday::create(['date' => '2026-11-20', 'name' => 'Consciência Negra']);

    $errors = scheduleErrors(fn () => ($this->schedule)(['scheduled_at' => local('2026-11-20 10:00')]));

    expect($errors['scheduled_at'][0])->toContain('Consciência Negra');
});

it('blocks the day once capacity is reached', function () {
    Appointment::factory()->count(4)->create(['scheduled_at' => local('2026-11-10 09:00')]);

    $errors = scheduleErrors(fn () => ($this->schedule)(['scheduled_at' => local('2026-11-10 14:00')]));

    expect($errors['scheduled_at'][0])->toBe(__('appointments.errors.day_full'));
});

it('respects the saturday capacity', function () {
    Appointment::factory()->count(2)->create(['scheduled_at' => local('2026-11-14 09:00')]);

    $errors = scheduleErrors(fn () => ($this->schedule)(['scheduled_at' => local('2026-11-14 10:00')]));

    expect($errors['scheduled_at'][0])->toBe(__('appointments.errors.day_full'));
});

it('allows overbooking when the owner disables blocking', function () {
    $settings = app(AgendaSettings::class);
    $settings->block_when_full = false;
    $settings->save();
    Appointment::factory()->count(4)->create(['scheduled_at' => local('2026-11-10 09:00')]);

    expect(($this->schedule)(['scheduled_at' => local('2026-11-10 14:00')])->exists)->toBeTrue();
});

it('frees capacity when an appointment is cancelled', function () {
    $appointments = Appointment::factory()->count(4)->create(['scheduled_at' => local('2026-11-10 09:00')]);
    $appointments->first()->update(['status' => AppointmentStatus::Cancelled]);

    expect(($this->schedule)(['scheduled_at' => local('2026-11-10 14:00')])->exists)->toBeTrue();
});

it('requires delivery on or after reception', function () {
    $errors = scheduleErrors(fn () => ($this->schedule)([
        'scheduled_at' => local('2026-11-10 09:00'),
        'promised_delivery_at' => local('2026-11-09 17:00'),
    ]));

    expect($errors['promised_delivery_at'][0])->toBe(__('appointments.errors.delivery_before_reception'));
});

it('lets staff edit notes of an appointment on a full day', function () {
    $appointments = Appointment::factory()->count(4)->create(['scheduled_at' => local('2026-11-10 09:00')]);

    $updated = app(UpdateAppointment::class)->execute($appointments->first(), ['notes' => 'Trazer chave reserva']);

    expect($updated->notes)->toBe('Trazer chave reserva');
});

it('rejects rescheduling into a full day', function () {
    Appointment::factory()->count(4)->create(['scheduled_at' => local('2026-11-10 09:00')]);
    $other = ($this->schedule)(['scheduled_at' => local('2026-11-11 09:00')]);

    $errors = scheduleErrors(fn () => app(UpdateAppointment::class)->execute($other, ['scheduled_at' => local('2026-11-10 15:00')]));

    expect($errors['scheduled_at'][0])->toBe(__('appointments.errors.day_full'));
});

it('keeps its own slot when rescheduling within the same day', function () {
    Appointment::factory()->count(3)->create(['scheduled_at' => local('2026-11-10 09:00')]);
    $mine = ($this->schedule)(['scheduled_at' => local('2026-11-10 10:00')]);

    $moved = app(UpdateAppointment::class)->execute($mine, ['scheduled_at' => local('2026-11-10 15:00')]);

    expect($moved->scheduled_at->equalTo(local('2026-11-10 15:00')))->toBeTrue();
});
