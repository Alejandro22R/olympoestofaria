<?php

use App\Domain\Auth\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Canales siempre privados o de presencia (PLAN.md §2.4).
 * Nunca se envían datos sensibles por el socket: solo identificadores y estados.
 */

// Canal personal de cada usuario (notificaciones, progreso de medios).
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

// Canal interno del taller: solo personal con acceso al panel.
Broadcast::channel('workshop', function (User $user): bool {
    return $user->is_active && $user->can(Permission::AccessPanel->value);
});
