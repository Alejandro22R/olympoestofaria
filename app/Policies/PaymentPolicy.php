<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\User;

class PaymentPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::LeadsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::LeadsManage;
    }

    public function create(User $user): bool
    {
        // Los pagos se crean desde la cotización u orden (Pix o registro manual).
        return false;
    }

    public function update(User $user, mixed $model): bool
    {
        return false;
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
