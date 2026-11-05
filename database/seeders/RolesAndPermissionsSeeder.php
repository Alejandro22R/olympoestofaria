<?php

namespace Database\Seeders;

use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea roles y permisos a partir de los enums. Idempotente: se puede ejecutar en cada despliegue.
 * La matriz es solo el valor inicial: si el dueño la editó, se respetan sus cambios y solo se
 * agregan los permisos nuevos que el rol trae por defecto. El dueño siempre tiene todos.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $new = [];
        foreach (Permission::cases() as $permission) {
            if (PermissionModel::query()->where('name', $permission->value)->where('guard_name', 'web')->doesntExist()) {
                $new[] = $permission->value;
            }
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (Permission::matrix() as $role => $permissions) {
            $model = RoleModel::findOrCreate($role, 'web');
            $defaults = array_map(fn (Permission $p) => $p->value, $permissions);

            if ($model->wasRecentlyCreated || $role === Role::Owner->value) {
                $model->syncPermissions($defaults);
            } elseif ($added = array_values(array_intersect($defaults, $new))) {
                $model->givePermissionTo($added);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
