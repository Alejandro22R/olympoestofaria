<?php

namespace App\Policies;

use App\Domain\Auth\Permission;

class AppointmentPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::AppointmentsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::AppointmentsManage;
    }
}
