<?php

use App\Domain\Auth\Role;

it('serves the portal in Brazilian Portuguese by default', function () {
    $this->get('/')->assertOk()->assertSee('Monte seu projeto')->assertSee('lang="pt-BR"', false);
});

it('switches the portal to Spanish with ?lang=es and remembers it', function () {
    $this->get('/?lang=es')->assertOk()->assertSee('Arma tu proyecto');
    $this->get('/')->assertSee('Arma tu proyecto');
});

it('ignores unsupported languages', function () {
    $this->get('/?lang=xx')->assertOk()->assertSee('Monte seu projeto');
});

it('uses the staff member language in the panel', function () {
    $this->actingAs(userWithRole(Role::Owner, ['locale' => 'es']))
        ->get('/admin/settings/general')
        ->assertOk()
        ->assertSee('Nombre comercial');
});
