<?php

namespace App\Policies;

use App\Domain\Auth\Permission;

class ServiceTypePolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::CatalogView;
    }

    protected function managePermission(): Permission
    {
        return Permission::CatalogManage;
    }
}
