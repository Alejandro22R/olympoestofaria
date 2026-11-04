<?php

use App\Domain\Auth\Role;
use App\Domain\Inspections\Support\VehicleSilhouettes;
use App\Domain\Settings\ProductionSettings;
use App\Domain\Settings\WarrantySettings;
use App\Filament\Clusters\Settings\Pages\ManageProductionSettings;
use App\Filament\Clusters\Settings\Pages\ManageWarrantySettings;
use App\Filament\Resources\VehicleSilhouettes\Pages\ManageVehicleSilhouettes;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleSilhouette;
use Livewire\Livewire;

beforeEach(function () {
    seedProduction();
    $this->actingAs(userWithRole(Role::Owner));
});

it('saves the production thresholds', function () {
    Livewire::test(ManageProductionSettings::class)
        ->fillForm(['stalled_after_hours' => 72])
        ->call('save')->assertHasNoFormErrors();

    expect(app(ProductionSettings::class)->refresh()->stalled_after_hours)->toBe(72);
});

it('saves the warranty defaults', function () {
    Livewire::test(ManageWarrantySettings::class)
        ->fillForm(['default_months' => 6, 'conditions.es' => 'Nuevas condiciones'])
        ->call('save')->assertHasNoFormErrors();

    expect(app(WarrantySettings::class)->refresh()->conditionsText('es'))->toBe('Nuevas condiciones');
});

it('keeps production settings, stages, warranty terms and silhouettes owner-only', function (string $path) {
    $this->get($path)->assertOk();
    $this->actingAs(userWithRole(Role::Reception))->get($path)->assertForbidden();
})->with(['/admin/settings/production', '/admin/settings/warranty', '/admin/settings/workflow-stages', '/admin/settings/warranty-terms', '/admin/settings/vehicle-silhouettes']);

it('validates the silhouette zones JSON', function () {
    Livewire::test(ManageVehicleSilhouettes::class)
        ->callAction('create', data: [
            'body_type' => 'suv', 'name' => ['pt_BR' => 'SUV'], 'view_box' => '0 0 200 400',
            'outline' => 'M0 0 L10 10 Z', 'zones' => '{"Bad Key": 1}',
        ])
        ->assertHasActionErrors(['zones']);

    Livewire::test(ManageVehicleSilhouettes::class)
        ->callAction('create', data: [
            'body_type' => 'suv', 'name' => ['pt_BR' => 'SUV'], 'view_box' => '0 0 200 400',
            'outline' => 'M0 0 L10 10 Z', 'zones' => '{"hood": {"x": 10, "y": 10, "w": 50, "h": 40}}',
        ])
        ->assertHasNoActionErrors();

    expect(VehicleSilhouette::where('body_type', 'suv')->first()->zones)->toHaveKey('hood');
});

it('uses the silhouette of the vehicle body type in the check-in', function () {
    VehicleSilhouettes::flush();
    $model = VehicleModel::query()->where('body_type', 'pickup')->first();
    $vehicle = Vehicle::factory()->create(['vehicle_model_id' => $model->id]);

    expect(VehicleSilhouettes::forVehicle($vehicle))->toBe('pickup')
        ->and(VehicleSilhouettes::options('pickup'))->toHaveKey('cargo_bed')
        ->and(VehicleSilhouettes::options('pickup'))->not->toHaveKey('trunk');
});
