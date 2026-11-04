<?php

use App\Domain\Auth\Role;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    // Usa el broadcaster real de Reverb (firma local, sin conexión al servidor).
    config(['broadcasting.default' => 'reverb']);
    Broadcast::forgetDrivers();
    require base_path('routes/channels.php');
});

function authChannel(string $channel): TestResponse
{
    return test()->post('/broadcasting/auth', ['channel_name' => $channel, 'socket_id' => '1234.5678']);
}

it('authorizes staff on the private workshop channel', function (Role $role) {
    $this->actingAs(userWithRole($role));

    authChannel('private-workshop')->assertOk();
})->with([Role::Owner, Role::Reception, Role::Upholsterer]);

it('rejects customers on the private workshop channel', function () {
    $this->actingAs(userWithRole(Role::Customer));

    authChannel('private-workshop')->assertForbidden();
});

it('only lets a user listen to their own user channel', function () {
    $me = userWithRole(Role::Customer);
    $other = userWithRole(Role::Customer);
    $this->actingAs($me);

    authChannel('private-App.Models.User.'.$me->id)->assertOk();
    authChannel('private-App.Models.User.'.$other->id)->assertForbidden();
});

it('rejects guests', function () {
    authChannel('private-workshop')->assertForbidden();
});
