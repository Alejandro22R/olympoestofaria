<?php

use App\Domain\Auth\Role;
use App\Filament\Pages\AppointmentCalendar;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\Vehicle;
use Livewire\Livewire;

beforeEach(function () {
    freezeMonday();
    $this->actingAs(userWithRole(Role::Reception));
    $this->vehicle = Vehicle::factory()->create();
});

it('creates an appointment from the panel', function () {
    Livewire::test(CreateAppointment::class)
        ->fillForm(['vehicle_id' => $this->vehicle->id, 'scheduled_at' => '2026-11-10 09:00:00'])
        ->call('create')
        ->assertHasNoFormErrors();

    $appointment = Appointment::sole();
    // Se guarda en UTC: 09:00 en São Paulo = 12:00 UTC.
    expect($appointment->scheduled_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-10 12:00');
});

it('shows the business rule error on the date field', function () {
    Appointment::factory()->count(4)->create(['scheduled_at' => local('2026-11-10 09:00')]);

    Livewire::test(CreateAppointment::class)
        ->fillForm(['vehicle_id' => $this->vehicle->id, 'scheduled_at' => '2026-11-10 10:00:00'])
        ->call('create')
        ->assertHasFormErrors(['scheduled_at']);
});

it('prefills the date when coming from the calendar', function () {
    Livewire::withQueryParams(['date' => '2026-11-12'])
        ->test(CreateAppointment::class)
        ->assertSchemaStateSet(['scheduled_at' => '2026-11-12 08:00'], 'form');
});

it('offers only allowed status transitions when editing', function () {
    $appointment = Appointment::factory()->create(['scheduled_at' => local('2026-11-10 09:00')]);

    Livewire::test(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['status' => 'confirmed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($appointment->fresh()->status->value)->toBe('confirmed');
});

it('renders the calendar with occupancy and holidays', function () {
    Appointment::factory()->count(2)->create(['scheduled_at' => local('2026-11-11 09:00')]);
    Holiday::create(['date' => '2026-11-20', 'name' => 'Consciência Negra']);

    Livewire::test(AppointmentCalendar::class)
        ->assertSee('2/4')
        ->assertSee('Consciência Negra')
        ->assertSee('novembro');
});

it('navigates months in the calendar', function () {
    Livewire::test(AppointmentCalendar::class)
        ->call('nextMonth')
        ->assertSet('month', '2026-12')
        ->call('previousMonth')
        ->call('previousMonth')
        ->assertSet('month', '2026-10');
});

it('hides the calendar from customers', function () {
    $this->actingAs(userWithRole(Role::Customer))->get('/admin/calendar')->assertForbidden();
});
