import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, CalendarClock, ReceiptText, Users } from 'lucide-react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import { InvoiceStatusBadge } from '@/components/invoice-status-badge';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import { dashboard } from '@/routes';
import type { Invoice, RecurringInvoice } from '@/types';

type Stat = {
    label: string;
    value: string;
    href: string;
};

type Props = {
    stats: Stat[];
    recentInvoices: Invoice[];
    upcomingRecurring: RecurringInvoice[];
    customerCount: number;
};

export default function Dashboard({
    stats,
    recentInvoices,
    upcomingRecurring,
    customerCount,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6">
                <div>
                    <Heading
                        title="Dashboard"
                        description="A quick look at your business."
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <Link
                            key={stat.label}
                            href={stat.href}
                            className="group"
                        >
                            <Card className="transition-colors group-hover:border-foreground/20">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {stat.label}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex items-center justify-between gap-2">
                                    <span className="text-2xl font-semibold tracking-tight">
                                        {stat.value}
                                    </span>
                                    <ArrowUpRight className="size-5 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                <div className="grid flex-1 gap-4 xl:grid-cols-3">
                    <Card className="xl:col-span-2">
                        <CardHeader className="flex-row items-center justify-between space-y-0">
                            <CardTitle>Recent invoices</CardTitle>
                            <ReceiptText className="size-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            {recentInvoices.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No invoices yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Number</TableHead>
                                            <TableHead>Customer</TableHead>
                                            <TableHead>Issued</TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {recentInvoices.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell className="font-medium">
                                                    <Link
                                                        href={InvoiceController.show.url(
                                                            {
                                                                invoice:
                                                                    invoice.id,
                                                            },
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
                                                    {formatDate(
                                                        invoice.issue_date,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatMoney(invoice.total)}
                                                </TableCell>
                                                <TableCell>
                                                    <InvoiceStatusBadge
                                                        status={invoice.status}
                                                        label={
                                                            invoice.status_label
                                                        }
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-4">
                        <Card>
                            <CardHeader className="flex-row items-center justify-between space-y-0">
                                <CardTitle>Upcoming recurring</CardTitle>
                                <CalendarClock className="size-5 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                {upcomingRecurring.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No active recurring invoices.
                                    </p>
                                ) : (
                                    <ul className="divide-y">
                                        {upcomingRecurring.map((schedule) => (
                                            <li
                                                key={schedule.id}
                                                className="flex items-center justify-between gap-2 py-2 text-sm first:pt-0 last:pb-0"
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium">
                                                        {schedule.customer
                                                            ?.billing_name ??
                                                            '—'}
                                                    </p>
                                                    <p className="text-muted-foreground">
                                                        {
                                                            schedule.frequency_label
                                                        }{' '}
                                                        ·{' '}
                                                        {formatDate(
                                                            schedule.next_invoice_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex-row items-center justify-between space-y-0">
                                <CardTitle>Customers</CardTitle>
                                <Users className="size-5 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {customerCount}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
