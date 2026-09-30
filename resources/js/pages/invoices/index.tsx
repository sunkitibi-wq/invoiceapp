import { Head, Link, router } from '@inertiajs/react';
import { FileText, Pencil, Plus, ReceiptText, Trash2 } from 'lucide-react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { DataTableSearch } from '@/components/data-table/data-table-search';
import { DataTableSortButton } from '@/components/data-table/data-table-sort-button';
import Heading from '@/components/heading';
import { InvoiceStatusBadge } from '@/components/invoice-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import type { Invoice, Option, Paginated } from '@/types';

type Props = {
    invoices: Paginated<Invoice>;
    filters: {
        search: string | null;
        status: string | null;
    };
    statuses: Option[];
    summary: {
        outstanding: string;
        overdue: number;
        draft: number;
    };
};

type PageProps = Props & {
    sort?: string | null;
    direction?: 'asc' | 'desc' | null;
};

export default function InvoicesIndex({
    invoices,
    filters,
    statuses,
    summary,
    sort = 'issue_date',
    direction = 'desc',
}: PageProps) {
    const sortable = { sort, direction };

    const changeStatus = (status: string) => {
        router.get(
            InvoiceController.index.url(),
            {
                status: status || undefined,
                search: filters.search || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const keep = { status: filters.status || undefined };

    return (
        <>
            <Head title="Invoices" />

            <div className="space-y-6">
                <div className="flex items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Invoices"
                        description="Track what your customers owe you."
                    />
                    <Button asChild>
                        <Link href={InvoiceController.create.url()}>
                            <Plus />
                            New invoice
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Outstanding
                                </p>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {summary.outstanding}
                                </p>
                            </div>
                            <FileText className="size-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Overdue
                                </p>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {summary.overdue}
                                </p>
                            </div>
                            <ReceiptText className="size-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Drafts
                                </p>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {summary.draft}
                                </p>
                            </div>
                            <Pencil className="size-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between gap-4 space-y-0">
                        <DataTableSearch
                            route={InvoiceController.index.url()}
                            defaultValue={filters.search ?? ''}
                            placeholder="Search invoices…"
                            keep={{ status: filters.status || undefined }}
                            className="w-full sm:w-64"
                        />
                        <Select
                            value={filters.status ?? ''}
                            onValueChange={changeStatus}
                        >
                            <SelectTrigger className="w-full sm:w-48">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All statuses</SelectItem>
                                {statuses.map((status) => (
                                    <SelectItem
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Number"
                                            sortKey="number"
                                            sort={sortable.sort ?? 'issue_date'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={InvoiceController.index.url()}
                                            defaultDirection="asc"
                                            keep={keep}
                                        />
                                    </TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Issued"
                                            sortKey="issue_date"
                                            sort={sortable.sort ?? 'issue_date'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={InvoiceController.index.url()}
                                            defaultDirection="desc"
                                            keep={keep}
                                        />
                                    </TableHead>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Due"
                                            sortKey="due_date"
                                            sort={sortable.sort ?? 'issue_date'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={InvoiceController.index.url()}
                                            defaultDirection="desc"
                                            keep={keep}
                                        />
                                    </TableHead>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Status"
                                            sortKey="status"
                                            sort={sortable.sort ?? 'issue_date'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={InvoiceController.index.url()}
                                            defaultDirection="asc"
                                            keep={keep}
                                        />
                                    </TableHead>
                                    <TableHead className="text-right">
                                        <DataTableSortButton
                                            label="Total"
                                            sortKey="total"
                                            sort={sortable.sort ?? 'issue_date'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={InvoiceController.index.url()}
                                            defaultDirection="desc"
                                            keep={keep}
                                        />
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No invoices found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    invoices.data.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell className="font-medium">
                                                <Link
                                                    href={InvoiceController.show.url(
                                                        { invoice: invoice.id },
                                                    )}
                                                    className="hover:underline"
                                                >
                                                    {invoice.number}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                {invoice.customer
                                                    ?.billing_name ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(invoice.issue_date)}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(invoice.due_date)}
                                            </TableCell>
                                            <TableCell>
                                                <InvoiceStatusBadge
                                                    status={invoice.status}
                                                    label={invoice.status_label}
                                                />
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {formatMoney(invoice.total)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                    title="View"
                                                >
                                                    <Link
                                                        href={InvoiceController.show.url(
                                                            {
                                                                invoice:
                                                                    invoice.id,
                                                            },
                                                        )}
                                                    >
                                                        <FileText />
                                                    </Link>
                                                </Button>
                                                {invoice.is_editable ? (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                            title="Edit"
                                                        >
                                                            <Link
                                                                href={InvoiceController.edit.url(
                                                                    {
                                                                        invoice:
                                                                            invoice.id,
                                                                    },
                                                                )}
                                                            >
                                                                <Pencil />
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDeleteDialog
                                                            title="Delete invoice"
                                                            description={
                                                                <>
                                                                    Are you sure
                                                                    you want to
                                                                    delete{' '}
                                                                    <span className="font-medium">
                                                                        {
                                                                            invoice.number
                                                                        }
                                                                    </span>
                                                                    ?
                                                                </>
                                                            }
                                                            form={InvoiceController.destroy.form(
                                                                {
                                                                    invoice:
                                                                        invoice.id,
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
                                                    </>
                                                ) : null}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        <DataTablePagination pagination={invoices} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

InvoicesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Invoices',
            href: InvoiceController.index.url(),
        },
    ],
};
