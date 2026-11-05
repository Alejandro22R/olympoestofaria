<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\User;

/**
 * Usuarios del equipo (Ajustes > Equipo). No se borran: se desactivan para conservar la auditoría.
 */
class UserPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::UsersManage;
    }

    protected function managePermission(): Permission
    {
        return Permission::UsersManage;
    }

    public function delete(User $user, mixed $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
