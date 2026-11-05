<?php

use App\Domain\Auth\Permission;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
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

// Presencia en una orden: quién la tiene abierta, para no pisar cambios. Solo id y nombre.
Broadcast::channel('work-orders.{workOrder}', function (User $user, WorkOrder $workOrder): array|false {
    return $user->can('view', $workOrder) ? ['id' => $user->id, 'name' => $user->name] : false;
});

// Canal privado del cliente (portal): estado de pagos y de su orden. Solo el propio cliente.
Broadcast::channel('customers.{customer}', function (User $user, Customer $customer): bool {
    return $user->customer?->id === $customer->id;
});
