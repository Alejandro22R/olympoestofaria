<?php

namespace App\Domain\Notifications;

use App\Domain\Auth\Permission;
use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Punto único para avisar al personal según permisos (y opcionalmente al dueño).
 */
class StaffNotifier
{
    /**
     * @return Collection<int, User>
     */
    public function withPermission(Permission $permission): Collection
    {
        return User::permission($permission->value)->where('is_active', true)->get();
    }

    public function notify(Permission $permission, StaffAlert $alert, ?User $except = null): void
    {
        $users = $this->withPermission($permission)->reject(fn (User $u) => $except !== null && $u->is($except));

        if ($users->isNotEmpty()) {
            Notification::send($users, $alert);
        }
    }
}
