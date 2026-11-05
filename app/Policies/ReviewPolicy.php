<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\User;

class ReviewPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::ReviewsManage;
    }

    protected function managePermission(): Permission
    {
        return Permission::ReviewsManage;
    }

    /** Las reseñas las crea el cliente desde el portal. */
    public function create(User $user): bool
    {
        return false;
    }
}
