<?php

use App\Domain\Auth\Role;
use App\Models\User;

it('lets staff roles into the admin panel', function (Role $role) {
    $this->actingAs(userWithRole($role))
        ->get('/admin')
        ->assertOk();
})->with([Role::Owner, Role::Reception, Role::Upholsterer]);

it('forbids customers from the admin panel', function () {
    $this->actingAs(userWithRole(Role::Customer))
        ->get('/admin')
        ->assertForbidden();
});

it('forbids inactive staff from the admin panel', function () {
    $this->actingAs(userWithRole(Role::Owner, ['is_active' => false]))
        ->get('/admin')
        ->assertForbidden();
});

it('redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forbids users without any role', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('renders the login page with the brand', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('images/brand/emblem.svg', false);
});
