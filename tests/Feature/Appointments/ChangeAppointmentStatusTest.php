<?php

use App\Domain\Appointments\Actions\ChangeAppointmentStatus;
use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

it('changes the status through an allowed transition and audits it', function () {
    $appointment = Appointment::factory()->create();

    app(ChangeAppointmentStatus::class)->execute($appointment, AppointmentStatus::Confirmed);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);

    $log = Activity::query()->where('subject_type', $appointment->getMorphClass())->where('event', 'updated')->latest('id')->first();
    expect($log->properties['old']['status'])->toBe('scheduled')
        ->and($log->properties['attributes']['status'])->toBe('confirmed');
});

it('refuses a forbidden transition', function () {
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Cancelled]);

    app(ChangeAppointmentStatus::class)->execute($appointment, AppointmentStatus::Confirmed);
})->throws(ValidationException::class);
