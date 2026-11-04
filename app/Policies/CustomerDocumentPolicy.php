<?php

namespace App\Policies;

use App\Domain\Auth\Permission;

class CustomerDocumentPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::CustomerDocumentsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::CustomerDocumentsManage;
    }
}
