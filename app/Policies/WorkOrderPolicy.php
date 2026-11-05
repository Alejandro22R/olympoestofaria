<?php

namespace App\Policies;

use App\Domain\Auth\Permission;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy extends PermissionPolicy
{
    protected function viewPermission(): Permission
    {
        return Permission::WorkOrdersView;
    }

    protected function managePermission(): Permission
    {
        return Permission::WorkOrdersManage;
    }

    /**
     * Mover de etapa y registrar avances: tapicero, recepción y dueño.
     */
    public function progress(User $user, WorkOrder $workOrder): bool
    {
        return $user->is_active && $user->can(Permission::WorkOrdersProgress->value) && $workOrder->status->isOpen();
    }

    /**
     * Seguimiento en el portal: el cliente dueño o el personal con acceso.
     */
    public function track(User $user, WorkOrder $workOrder): bool
    {
        return $user->customer?->id === $workOrder->vehicle->customer_id || $this->canView($user);
    }

    public function deliver(User $user, WorkOrder $workOrder): bool
    {
        return $user->is_active && $user->can(Permission::DeliveriesManage->value);
    }

    public function delete(User $user, mixed $model): bool
    {
        // Las órdenes no se borran: se cancelan (queda historial).
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
