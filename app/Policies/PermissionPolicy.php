<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\User;

/**
 * Base de las Policies del panel: "ver" y "gestionar" se resuelven con permisos de spatie.
 */
abstract class PermissionPolicy
{
    abstract protected function viewPermission(): Permission;

    abstract protected function managePermission(): Permission;

    protected function canView(User $user): bool
    {
        return $user->is_active && ($user->can($this->viewPermission()->value) || $user->can($this->managePermission()->value));
    }

    protected function canManage(User $user): bool
    {
        return $user->is_active && $user->can($this->managePermission()->value);
    }

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->canManage($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, mixed $model): bool
    {
        return $this->canManage($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, mixed $model): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
