<?php

use App\Domain\Appointments\Actions\ScheduleAppointment;
use App\Domain\Appointments\Events\AppointmentChanged;
use App\Domain\Auth\Role;
use App\Models\Vehicle;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('notifies the agenda team and refreshes calendars when an appointment is created or moved', function () {
    Notification::fake();
    Event::fake([AppointmentChanged::class]);
    freezeMonday();

    $creator = userWithRole(Role::Reception);
    $owner = userWithRole(Role::Owner);
    $upholsterer = userWithRole(Role::Upholsterer);
    $this->actingAs($creator);

    $appointment = app(ScheduleAppointment::class)->execute([
        'vehicle_id' => Vehicle::factory()->create()->id,
        'scheduled_at' => local('2026-11-10 09:00'),
    ]);

    Notification::assertSentTo($owner, StaffAlert::class, fn (StaffAlert $n) => $n->type === 'appointment_created');
    Notification::assertNotSentTo($creator, StaffAlert::class);
    Notification::assertNotSentTo($upholsterer, StaffAlert::class);
    Event::assertDispatched(AppointmentChanged::class, fn ($e) => $e->change === 'created');

    app(ScheduleAppointment::class)->execute(['scheduled_at' => local('2026-11-11 09:00')], $appointment);
    Event::assertDispatched(AppointmentChanged::class, fn ($e) => $e->change === 'moved');

    app(ScheduleAppointment::class)->execute(['notes' => 'x'], $appointment->fresh());
    Event::assertDispatchedTimes(AppointmentChanged::class, 2);
});

it('stores the alert in the bell in the user language', function () {
    freezeMonday();
    $owner = userWithRole(Role::Owner, ['locale' => 'es']);
    $this->actingAs(userWithRole(Role::Reception));

    app(ScheduleAppointment::class)->execute([
        'vehicle_id' => Vehicle::factory()->create(['plate' => 'ABC1D23'])->id,
        'scheduled_at' => local('2026-11-10 09:00'),
    ]);

    $data = $owner->notifications()->first()->data;
    expect($data['title'])->toBe('Nueva cita: ABC1D23')
        ->and($data['format'])->toBe('filament');
});
