<?php

namespace App\Domain\Auth;

/**
 * Catálogo de permisos del sistema. La matriz rol → permisos vive en
 * RolesAndPermissionsSeeder (valores iniciales); el dueño la edita en Ajustes > Roles y permisos.
 */
enum Permission: string
{
    case AccessPanel = 'panel.access';
    case SettingsManage = 'settings.manage';

    case CustomersView = 'customers.view';
    case CustomersManage = 'customers.manage';
    case CustomerDocumentsView = 'customer_documents.view';
    case CustomerDocumentsManage = 'customer_documents.manage';

    case VehiclesView = 'vehicles.view';
    case VehiclesManage = 'vehicles.manage';

    case CatalogView = 'catalog.view';
    case CatalogManage = 'catalog.manage';

    case AppointmentsView = 'appointments.view';
    case AppointmentsManage = 'appointments.manage';

    case InspectionsView = 'inspections.view';
    case InspectionsManage = 'inspections.manage';

    case WorkOrdersView = 'work_orders.view';
    case WorkOrdersManage = 'work_orders.manage';
    /** Mover tarjetas en el tablero y registrar avances (tapicero). */
    case WorkOrdersProgress = 'work_orders.progress';
    case DeliveriesManage = 'deliveries.manage';

    case LeadsView = 'leads.view';
    case LeadsManage = 'leads.manage';
    case MediaManage = 'media.manage';

    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';

    case ReviewsManage = 'reviews.manage';
    case ReportsView = 'reports.view';
    case UsersManage = 'users.manage';

    /**
     * @return array<string, list<self>>
     */
    public static function matrix(): array
    {
        $all = self::cases();

        return [
            Role::Owner->value => $all,
            Role::Reception->value => [
                self::AccessPanel,
                self::CustomersView, self::CustomersManage,
                self::CustomerDocumentsView, self::CustomerDocumentsManage,
                self::VehiclesView, self::VehiclesManage,
                self::CatalogView,
                self::AppointmentsView, self::AppointmentsManage,
                self::InspectionsView, self::InspectionsManage,
                self::WorkOrdersView, self::WorkOrdersManage, self::WorkOrdersProgress, self::DeliveriesManage,
                self::InventoryView, self::InventoryManage,
                self::LeadsView, self::LeadsManage, self::MediaManage,
                self::ReviewsManage,
            ],
            Role::Upholsterer->value => [
                self::AccessPanel,
                self::CustomersView,
                self::VehiclesView,
                self::CatalogView,
                self::AppointmentsView,
                self::InspectionsView,
                self::WorkOrdersView, self::WorkOrdersProgress,
                self::InventoryView,
            ],
            Role::Customer->value => [],
        ];
    }
}
