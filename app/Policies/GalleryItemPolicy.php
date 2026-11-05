<?php

namespace App\Policies;

use App\Domain\Auth\Permission;

class GalleryItemPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::MediaManage;
    }

    protected function managePermission(): Permission
    {
        return Permission::MediaManage;
    }
}
