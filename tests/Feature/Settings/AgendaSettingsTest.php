<?php

use App\Domain\Auth\Role;
use App\Domain\Settings\AgendaSettings;
use App\Filament\Clusters\Settings\Pages\ManageAgendaSettings;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(userWithRole(Role::Owner)));

it('saves the agenda settings', function () {
    Livewire::test(ManageAgendaSettings::class)
        ->fillForm(['vehicles_per_day' => 6, 'working_days' => [1, 2, 3, 4, 5], 'opens_at' => '07:30'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(AgendaSettings::class)->refresh();
    expect($settings->vehicles_per_day)->toBe(6)
        ->and($settings->working_days)->toBe([1, 2, 3, 4, 5])
        ->and($settings->opens_at)->toBe('07:30');
});

it('rejects values out of range', function (array $data, string $field) {
    Livewire::test(ManageAgendaSettings::class)
        ->fillForm($data)
        ->call('save')
        ->assertHasFormErrors([$field]);
})->with([
    'negative capacity' => [['vehicles_per_day' => -1], 'vehicles_per_day'],
    'no working days' => [['working_days' => []], 'working_days'],
    'closes before opening' => [['opens_at' => '18:00', 'closes_at' => '08:00'], 'closes_at'],
    'huge advance' => [['min_advance_hours' => 10000], 'min_advance_hours'],
]);

it('keeps holidays inside the owner-only settings', function (Role $role, int $status) {
    $this->actingAs(userWithRole($role))->get('/admin/settings/holidays')->assertStatus($status);
})->with([
    [Role::Owner, 200],
    [Role::Reception, 403],
]);
