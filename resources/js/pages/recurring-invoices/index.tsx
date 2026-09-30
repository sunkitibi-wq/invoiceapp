import { Head, Link } from '@inertiajs/react';
import { Pause, Pencil, Play, Plus, Repeat, Trash2 } from 'lucide-react';
import RecurringInvoiceController from '@/actions/App/Http/Controllers/RecurringInvoiceController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
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
import { formatDate } from '@/lib/format';
import type { Paginated, RecurringInvoice } from '@/types';

type Props = {
    recurringInvoices: Paginated<RecurringInvoice>;
    frequencies: Array<{ value: string; label: string }>;
};

type PageProps = Props & {
    sort?: string | null;
    direction?: 'asc' | 'desc' | null;
};

export default function RecurringInvoicesIndex({
    recurringInvoices,
    frequencies,
    sort = 'next_invoice_at',
    direction = 'asc',
}: PageProps) {
    const frequencyLabel = (value: string) =>
        frequencies.find((frequency) => frequency.value === value)?.label ??
        value;

    return (
        <>
            <Head title="Recurring Invoices" />

            <div className="space-y-6">
                <div className="flex items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Recurring Invoices"
                        description="Schedules that generate invoices automatically."
                    />
                    <Button asChild>
                        <Link href={RecurringInvoiceController.create.url()}>
                            <Plus />
                            New schedule
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between space-y-0">
                        <div className="flex items-center gap-2">
                            <Repeat className="size-4 text-muted-foreground" />
                            <span className="text-sm text-muted-foreground">
                                {recurringInvoices.total} schedule
                                {recurringInvoices.total === 1 ? '' : 's'}
                            </span>
                        </div>
                        <Badge variant="secondary">
                            {
                                recurringInvoices.data.filter(
                                    (item) => item.is_active,
                                ).length
                            }{' '}
                            active
                        </Badge>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Frequency"
                                            sortKey="frequency"
                                            sort={sort ?? 'next_invoice_at'}
                                            direction={direction ?? 'asc'}
                                            route={RecurringInvoiceController.index.url()}
                                        />
                                    </TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Next invoice"
                                            sortKey="next_invoice_at"
                                            sort={sort ?? 'next_invoice_at'}
                                            direction={direction ?? 'asc'}
                                            route={RecurringInvoiceController.index.url()}
                                            defaultDirection="desc"
                                        />
                                    </TableHead>
                                    <TableHead>Ends</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recurringInvoices.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No recurring invoices yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    recurringInvoices.data.map((recurring) => (
                                        <TableRow key={recurring.id}>
                                            <TableCell className="font-medium">
                                                {recurring.frequency_label}
                                            </TableCell>
                                            <TableCell>
                                                {recurring.customer
                                                    ?.billing_name ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(
                                                    recurring.next_invoice_at,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {recurring.end_at
                                                    ? formatDate(
                                                          recurring.end_at,
                                                      )
                                                    : '—'}
                                            </TableCell>
                                            <TableCell>
                                                {recurring.is_active ? (
                                                    <Badge variant="secondary">
                                                        Active
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Paused
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                    title={
                                                        recurring.is_active
                                                            ? 'Pause'
                                                            : 'Resume'
                                                    }
                                                >
                                                    <Link
                                                        method="post"
                                                        href={RecurringInvoiceController.toggle.url(
                                                            {
                                                                recurring_invoice:
                                                                    recurring.id,
                                                            },
                                                        )}
                                                    >
                                                        {recurring.is_active ? (
                                                            <Pause />
                                                        ) : (
                                                            <Play />
                                                        )}
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                    title="Edit"
                                                >
                                                    <Link
                                                        href={RecurringInvoiceController.edit.url(
                                                            {
                                                                recurring_invoice:
                                                                    recurring.id,
                                                            },
                                                        )}
                                                    >
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <ConfirmDeleteDialog
                                                    title="Delete schedule"
                                                    description={
                                                        <>
                                                            Delete this{' '}
                                                            {frequencyLabel(
                                                                recurring.frequency,
                                                            ).toLowerCase()}{' '}
                                                            schedule? Existing
                                                            invoices are kept.
                                                        </>
                                                    }
                                                    form={RecurringInvoiceController.destroy.form(
                                                        {
                                                            recurring_invoice:
                                                                recurring.id,
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
                        <DataTablePagination pagination={recurringInvoices} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

RecurringInvoicesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Recurring Invoices',
            href: RecurringInvoiceController.index.url(),
        },
    ],
};
