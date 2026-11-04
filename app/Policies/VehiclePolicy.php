<?php

namespace App\Policies;

use App\Domain\Auth\Permission;

class VehiclePolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::VehiclesView;
    }

    protected function managePermission(): Permission
    {
        return Permission::VehiclesManage;
    }
}
