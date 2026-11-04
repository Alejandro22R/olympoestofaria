<?php

use App\Domain\Auth\Role;

it('only lets the owner open the settings', function (Role $role, int $status) {
    $this->actingAs(userWithRole($role))
        ->get('/admin/settings/general')
        ->assertStatus($status);
})->with([
    'owner' => [Role::Owner, 200],
    'reception' => [Role::Reception, 403],
    'upholsterer' => [Role::Upholsterer, 403],
]);
