<?php

namespace App\Domain\Users\Actions;

use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matriz de permisos por rol (Ajustes > Equipo > Roles). El dueño siempre tiene todos y el rol
 * cliente ninguno; solo se editan recepción y tapicero. Cada cambio queda auditado.
 */
class UpdateRolePermissions
{
    /**
     * @return list<Role>
     */
    public static function editableRoles(): array
    {
        return [Role::Reception, Role::Upholsterer];
    }

    /**
     * @param  list<string>  $permissions
     */
    public function execute(Role $role, array $permissions): void
    {
        if (! in_array($role, self::editableRoles(), true)) {
            throw ValidationException::withMessages(['role' => __('users.errors.role_locked')]);
        }

        $valid = array_map(fn (Permission $p) => $p->value, Permission::cases());
        $permissions = array_values(array_unique(array_intersect($permissions, $valid)));

        // Sin acceso al panel el resto no sirve; los ajustes y usuarios son exclusivos del dueño.
        $permissions = array_values(array_diff($permissions, [Permission::SettingsManage->value, Permission::UsersManage->value]));
        if ($permissions !== [] && ! in_array(Permission::AccessPanel->value, $permissions, true)) {
            $permissions[] = Permission::AccessPanel->value;
        }

        $model = RoleModel::findByName($role->value, 'web');
        $before = $model->permissions->pluck('name')->sort()->values()->all();
        sort($permissions);

        if ($before === $permissions) {
            return;
        }

        $model->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('settings')->causedBy(auth()->user())->event('updated')
            ->withProperties([
                'group' => 'roles',
                'role' => $role->value,
                'old' => array_values(array_diff($before, $permissions)),
                'attributes' => array_values(array_diff($permissions, $before)),
            ])->log('roles.updated');
    }
}
