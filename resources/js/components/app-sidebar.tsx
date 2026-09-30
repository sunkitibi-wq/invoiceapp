import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    KeyRound,
    LayoutGrid,
    ReceiptText,
    Repeat,
    ShieldCheck,
    UserCog,
    Users,
    Wallet,
    Wrench,
} from 'lucide-react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import PermissionController from '@/actions/App/Http/Controllers/PermissionController';
import RecurringInvoiceController from '@/actions/App/Http/Controllers/RecurringInvoiceController';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import ServiceController from '@/actions/App/Http/Controllers/ServiceController';
import UserController from '@/actions/App/Http/Controllers/UserController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

/**
 * A navigation entry paired with the permission that reveals it.
 */
type GatedNavItem = NavItem & { permission: string };

const mainNavItems: GatedNavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        permission: 'invoices.view',
    },
    {
        title: 'Customers',
        href: CustomerController.index.url(),
        icon: Users,
        permission: 'customers.view',
    },
    {
        title: 'Services',
        href: ServiceController.index.url(),
        icon: Wrench,
        permission: 'services.view',
    },
    {
        title: 'Invoices',
        href: InvoiceController.index.url(),
        icon: ReceiptText,
        permission: 'invoices.view',
    },
    {
        title: 'Recurring Invoices',
        href: RecurringInvoiceController.index.url(),
        icon: Repeat,
        permission: 'recurring_invoices.view',
    },
    {
        title: 'Payments',
        href: PaymentController.index.url(),
        icon: Wallet,
        permission: 'payments.view',
    },
];

const accessNavItems: GatedNavItem[] = [
    {
        title: 'Users',
        href: UserController.index.url(),
        icon: UserCog,
        permission: 'users.view',
    },
    {
        title: 'Roles',
        href: RoleController.index.url(),
        icon: ShieldCheck,
        permission: 'roles.view',
    },
    {
        title: 'Permissions',
        href: PermissionController.index.url(),
        icon: KeyRound,
        permission: 'permissions.view',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const permissions = usePage().props.permissions ?? [];

    const permitted = (items: GatedNavItem[]): NavItem[] =>
        items
            .filter((item) => permissions.includes(item.permission))
            .map(({ permission: _permission, ...item }) => item);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={permitted(mainNavItems)} label="Platform" />
                {permitted(accessNavItems).length > 0 ? (
                    <NavMain
                        items={permitted(accessNavItems)}
                        label="Access control"
                    />
                ) : null}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
