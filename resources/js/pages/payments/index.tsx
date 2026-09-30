import { Head, Link, router } from '@inertiajs/react';
import { Banknote, FileText, Trash2, Wallet } from 'lucide-react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { DataTableSearch } from '@/components/data-table/data-table-search';
import { DataTableSortButton } from '@/components/data-table/data-table-sort-button';
import Heading from '@/components/heading';
import { PaymentMethodBadge } from '@/components/payment-method-badge';
import { RecordPaymentDialog } from '@/components/record-payment-dialog';
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
import type { Option, Paginated, Payment } from '@/types';

type Props = {
    payments: Paginated<Payment>;
    filters: {
        search: string | null;
        method: string | null;
        period: string | null;
    };
    paymentMethods: Option[];
    periods: Option[];
    summary: {
        received: string;
        count: number;
        outstanding: string;
    };
};

type PageProps = Props & {
    sort?: string | null;
    direction?: 'asc' | 'desc' | null;
};

const keepFilters = (filters: Props['filters']) => ({
    search: filters.search || undefined,
    method: filters.method || undefined,
    period: filters.period || undefined,
});

export default function PaymentsIndex({
    payments,
    filters,
    paymentMethods,
    periods,
    summary,
    sort = 'paid_at',
    direction = 'desc',
}: PageProps) {
    const sortable = { sort, direction };
    const keep = keepFilters(filters);

    const applyFilter = (key: 'method' | 'period', value: string) => {
        router.get(
            PaymentController.index.url(),
            {
                ...keep,
                [key]: value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <>
            <Head title="Payments" />

            <div className="space-y-6">
                <div className="flex items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Payments"
                        description="Every payment you have recorded, across all invoices."
                    />
                    <RecordPaymentDialog
                        paymentMethods={paymentMethods}
                        onRecorded={() =>
                            router.reload({ only: ['payments', 'summary'] })
                        }
                    />
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Received
                                </p>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {summary.received}
                                </p>
                            </div>
                            <Banknote className="size-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Payments
                                </p>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {summary.count}
                                </p>
                            </div>
                            <Wallet className="size-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
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
                </div>

                <Card>
                    <CardHeader className="flex-row flex-wrap items-center justify-between gap-4 space-y-0">
                        <DataTableSearch
                            route={PaymentController.index.url()}
                            defaultValue={filters.search ?? ''}
                            placeholder="Search payments…"
                            keep={{
                                method: filters.method || undefined,
                                period: filters.period || undefined,
                            }}
                            className="w-full sm:w-64"
                        />
                        <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                            <Select
                                value={filters.method ?? ''}
                                onValueChange={(value) =>
                                    applyFilter('method', value)
                                }
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="All methods" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">
                                        All methods
                                    </SelectItem>
                                    {paymentMethods.map((method) => (
                                        <SelectItem
                                            key={method.value}
                                            value={method.value}
                                        >
                                            {method.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.period ?? ''}
                                onValueChange={(value) =>
                                    applyFilter('period', value)
                                }
                            >
                                <SelectTrigger className="w-full sm:w-44">
                                    <SelectValue placeholder="All time" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All time</SelectItem>
                                    {periods.map((period) => (
                                        <SelectItem
                                            key={period.value}
                                            value={period.value}
                                        >
                                            {period.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Paid on"
                                            sortKey="paid_at"
                                            sort={sortable.sort ?? 'paid_at'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={PaymentController.index.url()}
                                            defaultDirection="desc"
                                            keep={keep}
                                        />
                                    </TableHead>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Method"
                                            sortKey="method"
                                            sort={sortable.sort ?? 'paid_at'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={PaymentController.index.url()}
                                            defaultDirection="asc"
                                            keep={keep}
                                        />
                                    </TableHead>
                                    <TableHead>Reference</TableHead>
                                    <TableHead className="text-right">
                                        <DataTableSortButton
                                            label="Amount"
                                            sortKey="amount"
                                            sort={sortable.sort ?? 'paid_at'}
                                            direction={
                                                sortable.direction ?? 'desc'
                                            }
                                            route={PaymentController.index.url()}
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
                                {payments.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No payments found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    payments.data.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell className="font-medium">
                                                {payment.invoice ? (
                                                    <Link
                                                        href={InvoiceController.show.url(
                                                            {
                                                                invoice:
                                                                    payment
                                                                        .invoice
                                                                        .id,
                                                            },
                                                        )}
                                                        className="hover:underline"
                                                    >
                                                        {payment.invoice.number}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {payment.invoice?.customer
                                                    ?.billing_name ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(payment.paid_at)}
                                            </TableCell>
                                            <TableCell>
                                                <PaymentMethodBadge
                                                    method={payment.method}
                                                    label={payment.method_label}
                                                />
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {payment.reference ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {formatMoney(payment.amount)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {payment.invoice ? (
                                                    <ConfirmDeleteDialog
                                                        title="Delete payment"
                                                        description={`Remove this ${formatMoney(payment.amount)} payment from ${payment.invoice.number}? The invoice balance will be recalculated.`}
                                                        form={PaymentController.destroy.form(
                                                            {
                                                                invoice:
                                                                    payment
                                                                        .invoice
                                                                        .id,
                                                                payment:
                                                                    payment.id,
                                                            },
                                                        )}
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title="Delete payment"
                                                        >
                                                            <Trash2 className="text-destructive" />
                                                        </Button>
                                                    </ConfirmDeleteDialog>
                                                ) : null}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        <DataTablePagination pagination={payments} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PaymentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Payments',
            href: PaymentController.index.url(),
        },
    ],
};
