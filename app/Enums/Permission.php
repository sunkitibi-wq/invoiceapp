<?php

namespace App\Enums;

/**
 * The complete set of abilities the application checks.
 *
 * Names are written as "<resource>.<action>" so the permission matrix can group
 * them without a second lookup table. A user's effective abilities are the
 * union of the permissions attached to their roles.
 */
enum Permission: string
{
    case ViewCustomers = 'customers.view';
    case CreateCustomers = 'customers.create';
    case UpdateCustomers = 'customers.update';
    case DeleteCustomers = 'customers.delete';

    case ViewServices = 'services.view';
    case CreateServices = 'services.create';
    case UpdateServices = 'services.update';
    case DeleteServices = 'services.delete';

    case ViewInvoices = 'invoices.view';
    case CreateInvoices = 'invoices.create';
    case UpdateInvoices = 'invoices.update';
    case DeleteInvoices = 'invoices.delete';
    case SendInvoices = 'invoices.send';
    case VoidInvoices = 'invoices.void';

    case ViewPayments = 'payments.view';
    case RecordPayments = 'payments.record';
    case DeletePayments = 'payments.delete';

    case ViewRecurringInvoices = 'recurring_invoices.view';
    case CreateRecurringInvoices = 'recurring_invoices.create';
    case UpdateRecurringInvoices = 'recurring_invoices.update';
    case DeleteRecurringInvoices = 'recurring_invoices.delete';

    case ViewUsers = 'users.view';
    case CreateUsers = 'users.create';
    case UpdateUsers = 'users.update';
    case DeleteUsers = 'users.delete';

    case ViewRoles = 'roles.view';
    case CreateRoles = 'roles.create';
    case UpdateRoles = 'roles.update';
    case DeleteRoles = 'roles.delete';

    case ViewPermissions = 'permissions.view';
    case CreatePermissions = 'permissions.create';
    case UpdatePermissions = 'permissions.update';
    case DeletePermissions = 'permissions.delete';

    /**
     * Get the label for the action this permission performs.
     */
    public function actionLabel(): string
    {
        return match ($this->action()) {
            'view' => 'View',
            'create' => 'Create',
            'update' => 'Edit',
            'delete' => 'Delete',
            'send' => 'Send',
            'void' => 'Void',
            'record' => 'Record',
            default => ucfirst($this->action()),
        };
    }

    /**
     * Get the section of the application this permission belongs to.
     */
    public function group(): string
    {
        return match ($this) {
            self::ViewCustomers, self::CreateCustomers, self::UpdateCustomers, self::DeleteCustomers => 'Customers',
            self::ViewServices, self::CreateServices, self::UpdateServices, self::DeleteServices => 'Services',
            self::ViewInvoices, self::CreateInvoices, self::UpdateInvoices, self::DeleteInvoices, self::SendInvoices, self::VoidInvoices => 'Invoices',
            self::ViewPayments, self::RecordPayments, self::DeletePayments => 'Payments',
            self::ViewRecurringInvoices, self::CreateRecurringInvoices, self::UpdateRecurringInvoices, self::DeleteRecurringInvoices => 'Recurring invoices',
            self::ViewUsers, self::CreateUsers, self::UpdateUsers, self::DeleteUsers => 'Users',
            self::ViewRoles, self::CreateRoles, self::UpdateRoles, self::DeleteRoles => 'Roles',
            self::ViewPermissions, self::CreatePermissions, self::UpdatePermissions, self::DeletePermissions => 'Permissions',
        };
    }

    /**
     * Get the resource this permission applies to.
     */
    public function resource(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Get the action this permission performs.
     */
    public function action(): string
    {
        return explode('.', $this->value)[1];
    }

    /**
     * Get the permissions grouped by section, in display order.
     *
     * The role editor renders one row of checkboxes per group, so the order
     * here is the order an administrator reads them in.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }

        return $grouped;
    }
}
