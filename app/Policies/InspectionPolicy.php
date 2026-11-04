<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\User;

class InspectionPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::InspectionsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::InspectionsManage;
    }

    public function delete(User $user, mixed $model): bool
    {
        // El acta es un registro de prueba: no se borra desde el panel.
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
