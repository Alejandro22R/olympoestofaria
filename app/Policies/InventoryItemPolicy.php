<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\User;

class InventoryItemPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::InventoryView;
    }

    protected function managePermission(): Permission
    {
        return Permission::InventoryManage;
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
