<?php

namespace App\Policies;

use App\Domain\Auth\Permission;

class CustomerPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::CustomersView;
    }

    protected function managePermission(): Permission
    {
        return Permission::CustomersManage;
    }
}
