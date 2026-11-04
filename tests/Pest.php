<?php

use App\Domain\Auth\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

function userWithRole(Role $role, array $attributes = []): User
{
    return User::factory()->withRole($role)->create($attributes);
}

/**
 * Instante expresado en la hora local del taller (America/Sao_Paulo por defecto).
 */
function local(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'America/Sao_Paulo');
}

/**
 * Congela el reloj en un lunes laborable: 2026-11-09 08:00 en São Paulo.
 */
function freezeMonday(): void
{
    test()->travelTo(local('2026-11-09 08:00'));
}
