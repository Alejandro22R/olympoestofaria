<?php

namespace Database\Seeders;

use App\Domain\Auth\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea roles y permisos a partir de los enums. Idempotente: se puede ejecutar en cada despliegue.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (Permission::matrix() as $role => $permissions) {
            RoleModel::findOrCreate($role, 'web')
                ->syncPermissions(array_map(fn (Permission $p) => $p->value, $permissions));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
