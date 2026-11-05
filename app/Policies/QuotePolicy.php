<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::LeadsView;
    }

    protected function managePermission(): Permission
    {
        return Permission::LeadsManage;
    }

    /**
     * Ver y aprobar en el portal: el cliente dueño (o el personal con acceso).
     */
    public function respond(User $user, Quote $quote): bool
    {
        return $user->customer?->id === $quote->customer_id || $this->canView($user);
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
