import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Wrench } from 'lucide-react';
import ServiceController from '@/actions/App/Http/Controllers/ServiceController';
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
import { formatMoney } from '@/lib/format';
import type { Paginated, Service } from '@/types';

type Props = {
    services: Paginated<Service>;
    filters: {
        search: string | null;
    };
};

type PageProps = Props & {
    sort?: string | null;
    direction?: 'asc' | 'desc' | null;
};

export default function ServicesIndex({
    services,
    filters,
    sort = 'name',
    direction = 'asc',
}: PageProps) {
    return (
        <>
            <Head title="Services" />

            <div className="space-y-6">
                <div className="flex items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Services"
                        description="Products and services you add to invoices."
                    />
                    <Button asChild>
                        <Link href={ServiceController.create.url()}>
                            <Plus />
                            New service
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between gap-4 space-y-0">
                        <div className="flex items-center gap-2">
                            <Wrench className="size-4 text-muted-foreground" />
                            <span className="text-sm text-muted-foreground">
                                {services.total} service
                                {services.total === 1 ? '' : 's'}
                            </span>
                        </div>
                        <DataTableSearch
                            route={ServiceController.index.url()}
                            defaultValue={filters.search ?? ''}
                            placeholder="Search services…"
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
                                            sort={sort ?? 'name'}
                                            direction={direction ?? 'asc'}
                                            route={ServiceController.index.url()}
                                        />
                                    </TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-right">
                                        <DataTableSortButton
                                            label="Unit price"
                                            sortKey="unit_price"
                                            sort={sort ?? 'name'}
                                            direction={direction ?? 'asc'}
                                            route={ServiceController.index.url()}
                                        />
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Tax
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {services.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No services found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    services.data.map((service) => (
                                        <TableRow key={service.id}>
                                            <TableCell className="font-medium">
                                                <Link
                                                    href={ServiceController.edit.url(
                                                        {
                                                            service: service.id,
                                                        },
                                                    )}
                                                    className="hover:underline"
                                                >
                                                    {service.name}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {service.unit ?? '—'}
                                            </TableCell>
                                            <TableCell className="max-w-72 truncate text-muted-foreground">
                                                {service.description ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {formatMoney(
                                                    service.unit_price,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right text-muted-foreground">
                                                {service.tax_rate === null
                                                    ? '—'
                                                    : `${(service.tax_rate * 100).toFixed(1).replace(/\.0$/, '')}%`}
                                            </TableCell>
                                            <TableCell>
                                                {service.is_active ? (
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
                                                        href={ServiceController.edit.url(
                                                            {
                                                                service:
                                                                    service.id,
                                                            },
                                                        )}
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <ConfirmDeleteDialog
                                                    title="Delete service"
                                                    description={
                                                        <>
                                                            Are you sure you
                                                            want to delete{' '}
                                                            <span className="font-medium">
                                                                {service.name}
                                                            </span>
                                                            ? If invoices
                                                            reference it, the
                                                            service will be
                                                            archived instead.
                                                        </>
                                                    }
                                                    form={ServiceController.destroy.form(
                                                        { service: service.id },
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
                        <DataTablePagination pagination={services} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ServicesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Services',
            href: ServiceController.index.url(),
        },
    ],
};
