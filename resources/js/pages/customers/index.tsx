import { Head, Link } from '@inertiajs/react';
import { Plus, Pencil, Trash2, Users } from 'lucide-react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { DataTableSearch } from '@/components/data-table/data-table-search';
import { DataTableSortButton } from '@/components/data-table/data-table-sort-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Customer, Paginated } from '@/types';

type Props = {
    customers: Paginated<Customer>;
    filters: {
        search: string | null;
    };
};

type SortProps = {
    sort: string | null;
    direction: 'asc' | 'desc' | null;
};

type PageProps = Props & {
    sort?: string | null;
    direction?: 'asc' | 'desc' | null;
};

export default function CustomersIndex({
    customers,
    filters,
    sort = 'name',
    direction = 'asc',
}: PageProps) {
    const sortable: SortProps = { sort, direction };

    return (
        <>
            <Head title="Customers" />

            <div className="space-y-6">
                <div className="flex items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Customers"
                        description="Manage the businesses and people you invoice."
                    />
                    <Button asChild>
                        <Link href={CustomerController.create.url()}>
                            <Plus />
                            New customer
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between gap-4 space-y-0">
                        <div className="flex items-center gap-2">
                            <Users className="size-4 text-muted-foreground" />
                            <span className="text-sm text-muted-foreground">
                                {customers.total} customer
                                {customers.total === 1 ? '' : 's'}
                            </span>
                        </div>
                        <DataTableSearch
                            route={CustomerController.index.url()}
                            defaultValue={filters.search ?? ''}
                            placeholder="Search customers…"
                            className="w-full sm:w-64"
                        />
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Name"
                                            sortKey="name"
                                            sort={sortable.sort ?? ''}
                                            direction={
                                                sortable.direction ?? 'asc'
                                            }
                                            route={CustomerController.index.url()}
                                        />
                                    </TableHead>
                                    <TableHead>Company</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead className="text-right">
                                        Invoices
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Outstanding
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {customers.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No customers found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    customers.data.map((customer) => (
                                        <TableRow key={customer.id}>
                                            <TableCell className="font-medium">
                                                <Link
                                                    href={CustomerController.edit.url(
                                                        {
                                                            customer:
                                                                customer.id,
                                                        },
                                                    )}
                                                    className="hover:underline"
                                                >
                                                    {customer.name}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {customer.company ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {customer.email ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground">
                                                {customer.invoices_count ?? 0}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {customer.outstanding_invoices_count ? (
                                                    <Badge variant="destructive">
                                                        {
                                                            customer.outstanding_invoices_count
                                                        }
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        0
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {customer.is_active ? (
                                                    <Badge variant="secondary">
                                                        Active
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Inactive
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                    title="Edit"
                                                >
                                                    <Link
                                                        href={CustomerController.edit.url(
                                                            {
                                                                customer:
                                                                    customer.id,
                                                            },
                                                        )}
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <ConfirmDeleteDialog
                                                    title="Delete customer"
                                                    description={
                                                        <>
                                                            Are you sure you
                                                            want to delete{' '}
                                                            <span className="font-medium">
                                                                {customer.name}
                                                            </span>
                                                            ? This action cannot
                                                            be undone.
                                                        </>
                                                    }
                                                    form={CustomerController.destroy.form(
                                                        {
                                                            customer:
                                                                customer.id,
                                                        },
                                                    )}
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="text-destructive" />
                                                    </Button>
                                                </ConfirmDeleteDialog>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        <DataTablePagination pagination={customers} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CustomersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Customers',
            href: CustomerController.index.url(),
        },
    ],
};
