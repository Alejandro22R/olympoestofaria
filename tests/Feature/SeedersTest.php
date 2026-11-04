<?php

use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use App\Models\Material;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\VehicleModel;

it('seeds demo data with a role per demo user', function () {
    $this->seed();

    expect(ServiceType::count())->toBeGreaterThanOrEqual(6)
        ->and(Material::count())->toBeGreaterThanOrEqual(3)
        ->and(VehicleModel::count())->toBe(3)
        ->and(User::where('email', 'owner@olympo.test')->first()->hasRole(Role::Owner->value))->toBeTrue();
});

it('gives settings.manage only to the owner', function (Role $role, bool $expected) {
    expect(userWithRole($role)->can(Permission::SettingsManage->value))->toBe($expected);
})->with([
    [Role::Owner, true],
    [Role::Reception, false],
    [Role::Upholsterer, false],
    [Role::Customer, false],
]);

it('is idempotent', function () {
    $this->seed();
    $this->seed();

    expect(VehicleModel::count())->toBe(3);
});

it('stores catalog names in both languages', function () {
    $this->seed();
    $seats = ServiceType::where('slug', 'seats')->first();

    expect($seats->getTranslation('name', 'pt_BR'))->toBe('Bancos')
        ->and($seats->getTranslation('name', 'es'))->toBe('Asientos');
});
