<?php

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Domain\Auth\Role;
use App\Domain\Inspections\Actions\RegisterCheckIn;
use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Settings\NotificationSettings;
use App\Filament\Resources\Appointments\Pages\CheckInAppointment;
use App\Filament\Resources\Inspections\Pages\ViewInspection;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\DamageType;
use App\Models\Inspection;
use App\Models\Vehicle;
use Database\Seeders\CheckinCatalogSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('private');
    Mail::fake();
    $this->seed(CheckinCatalogSeeder::class);
    $this->actingAs($this->reception = userWithRole(Role::Reception));
    $customer = Customer::factory()->create(['email' => 'ana@example.com']);
    $this->appointment = Appointment::factory()->for(Vehicle::factory()->for($customer)->state(['mileage_km' => 30_000]))->create();
});

it('prefills the last known mileage', function () {
    Livewire::test(CheckInAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertSchemaStateSet(['mileage_km' => 30_000], 'form');
});

it('registers a check-in from the panel with a damage mark and photo', function () {
    $type = DamageType::first();

    Livewire::test(CheckInAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->fillForm([
            'mileage_km' => 31_000,
            'fuel_level' => 'quarter',
            'lgpd_consent' => true,
            'marks' => [
                'm1' => ['zone' => 'rear_seat', 'damage_type_id' => $type->id, 'x' => 50, 'y' => 65, 'note' => 'Mancha',
                    'photos' => [UploadedFile::fake()->image('foto.jpg', 640, 480)]],
            ],
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $inspection = Inspection::sole();
    expect($inspection->marks->first()->photos)->toHaveCount(1)
        ->and($inspection->marks->first()->photos[0])->toStartWith('inspections/photos/');
    Storage::disk('private')->assertExists($inspection->marks->first()->photos[0]);
});

it('requires the LGPD consent in the form (no signature)', function () {
    Livewire::test(CheckInAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->fillForm(['mileage_km' => 31_000, 'fuel_level' => 'quarter', 'lgpd_consent' => false])
        ->call('register')
        ->assertHasFormErrors(['lgpd_consent']);

    expect(Inspection::count())->toBe(0);
});

it('requires the damage type on each mark', function () {
    Livewire::test(CheckInAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->fillForm([
            'mileage_km' => 31_000, 'fuel_level' => 'quarter', 'lgpd_consent' => true,
            'marks' => ['m1' => ['zone' => 'hood', 'damage_type_id' => null, 'photos' => []]],
        ])
        ->call('register')
        ->assertHasFormErrors(['marks.m1.damage_type_id']);
});

it('blocks the check-in page for cancelled or already received appointments', function () {
    $this->appointment->update(['status' => AppointmentStatus::Cancelled]);

    Livewire::test(CheckInAppointment::class, ['record' => $this->appointment->getRouteKey()])->assertForbidden();
});

it('blocks the check-in page for the upholsterer', function () {
    $this->actingAs(userWithRole(Role::Upholsterer));

    $this->get("/admin/appointments/{$this->appointment->id}/check-in")->assertForbidden();
});

it('sends the report by email from the inspection page and shows the log', function () {
    // Solo el envío manual (el automático tiene su propia prueba).
    $settings = app(NotificationSettings::class);
    $settings->auto_send_checkin_report = false;
    $settings->save();

    $inspection = app(RegisterCheckIn::class)->execute($this->appointment, [
        'mileage_km' => 31_000, 'fuel_level' => 'half', 'lgpd_consent' => true, 'marks' => [],
    ], $this->reception);

    Livewire::test(ViewInspection::class, ['record' => $inspection->getRouteKey()])
        ->callAction('send', data: ['channel' => ReportChannel::Email->value])
        ->assertHasNoActionErrors()
        ->assertSee('ana@example.com');

    expect($inspection->sends()->count())->toBe(1);
});

it('cannot delete an inspection', function () {
    $inspection = app(RegisterCheckIn::class)->execute($this->appointment, [
        'mileage_km' => 31_000, 'fuel_level' => 'half', 'lgpd_consent' => true, 'marks' => [],
    ], $this->reception);

    expect(userWithRole(Role::Owner)->can('delete', $inspection))->toBeFalse();
});
