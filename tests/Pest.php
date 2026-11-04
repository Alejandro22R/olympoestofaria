<?php

use App\Domain\Auth\Role;
use App\Models\User;
use App\Models\WorkflowStage;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CheckinCatalogSeeder;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SilhouetteSeeder;
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

/**
 * Siembra las etapas, plazos de garantía y catálogo de check-in usados por la producción.
 */
function seedProduction(): void
{
    test()->seed([
        CatalogSeeder::class,
        CheckinCatalogSeeder::class,
        SilhouetteSeeder::class,
        ProductionSeeder::class,
    ]);
}

function stage(string $namePt): WorkflowStage
{
    return WorkflowStage::query()->where('name->pt_BR', $namePt)->firstOrFail();
}
