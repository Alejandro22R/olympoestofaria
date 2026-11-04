<?php

use App\Domain\Auth\Role;
use App\Filament\Resources\Vehicles\Pages\CreateVehicle;
use App\Models\Customer;
use App\Models\Vehicle;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(userWithRole(Role::Reception));
    $this->customer = Customer::factory()->create();
});

function vehicleData(array $overrides = []): array
{
    return [
        'customer_id' => test()->customer->id,
        'plate' => 'ABC-1234',
        'brand' => 'Volkswagen',
        'model' => 'Fusca',
        'year' => 1975,
        'mileage_km' => 120000,
        ...$overrides,
    ];
}

it('creates a vehicle with the plate normalized', function () {
    Livewire::test(CreateVehicle::class)
        ->fillForm(vehicleData())
        ->call('create')
        ->assertHasNoFormErrors();

    $vehicle = Vehicle::sole();
    expect($vehicle->plate)->toBe('ABC1234')
        ->and($vehicle->formattedPlate())->toBe('ABC-1234');
});

it('validates the vehicle fields', function (array $data, string $field) {
    Livewire::test(CreateVehicle::class)
        ->fillForm(vehicleData($data))
        ->call('create')
        ->assertHasFormErrors([$field]);
})->with([
    'bad plate' => [['plate' => 'AB-12'], 'plate'],
    'old year' => [['year' => 1949], 'year'],
    'future year' => [['year' => (int) date('Y') + 2], 'year'],
    'negative km' => [['mileage_km' => -5], 'mileage_km'],
]);

it('treats ABC-1234 and ABC1234 as the same plate', function () {
    Vehicle::factory()->create(['plate' => 'ABC1234']);

    Livewire::test(CreateVehicle::class)
        ->fillForm(vehicleData(['plate' => 'abc-1234']))
        ->call('create')
        ->assertHasFormErrors(['plate']);
});
