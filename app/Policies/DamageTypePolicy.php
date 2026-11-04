<?php

namespace App\Policies;

use App\Domain\Auth\Permission;

class DamageTypePolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::SettingsManage;
    }

    protected function managePermission(): Permission
    {
        return Permission::SettingsManage;
    }
}
