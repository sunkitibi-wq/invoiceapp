import { Head, Link } from '@inertiajs/react';
import {
    BadgeCheck,
    Download,
    Mail,
    Pencil,
    Send,
    Trash2,
    XCircle,
} from 'lucide-react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import { InvoiceStatusBadge } from '@/components/invoice-status-badge';
import { RecordPaymentDialog } from '@/components/record-payment-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate, formatMoney } from '@/lib/format';
import type { Invoice, Option } from '@/types';

type Props = {
    invoice: Invoice;
    paymentMethods: Option[];
};

export default function InvoicesShow({ invoice, paymentMethods }: Props) {
    const customer = invoice.customer;
    const canRecordPayment = !['draft', 'void'].includes(invoice.status);
    const canSend =
        Boolean(customer?.email) && !['paid', 'void'].includes(invoice.status);
    const canVoid = !['paid', 'void'].includes(invoice.status);

    return (
        <>
            <Head title={invoice.number} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h2 className="text-xl font-semibold tracking-tight">
                                {invoice.number}
                            </h2>
                            <InvoiceStatusBadge
                                status={invoice.status}
                                label={invoice.status_label}
                            />
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Issued {formatDate(invoice.issue_date)} · Due{' '}
                            {formatDate(invoice.due_date)}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={InvoiceController.pdf.url({
                                    invoice: invoice.id,
                                })}
                                target="_blank"
                            >
                                <Download />
                                PDF
                            </Link>
                        </Button>
                        {invoice.is_editable ? (
                            <Button variant="secondary" size="sm" asChild>
                                <Link
                                    href={InvoiceController.edit.url({
                                        invoice: invoice.id,
                                    })}
                                >
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                        ) : null}
                        {canSend ? (
                            <Button variant="secondary" size="sm" asChild>
                                <Link
                                    method="post"
                                    href={InvoiceController.send.url({
                                        invoice: invoice.id,
                                    })}
                                >
                                    <Send />
                                    Send
                                </Link>
                            </Button>
                        ) : null}
                        {canRecordPayment ? (
                            <RecordPaymentDialog
                                invoice={invoice}
                                paymentMethods={paymentMethods}
                            />
                        ) : null}{' '}
                        {canVoid ? (
                            <ConfirmDeleteDialog
                                title="Void invoice"
                                confirmLabel="Void"
                                description="Voiding marks this invoice as void and stops it being collected. The invoice can no longer be edited or paid."
                                form={InvoiceController.void.form({
                                    invoice: invoice.id,
                                })}
                            >
                                <Button variant="outline" size="sm">
                                    <XCircle />
                                    Void
                                </Button>
                            </ConfirmDeleteDialog>
                        ) : null}
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={InvoiceController.index.url()}>
                                Back
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader className="flex-row items-center justify-between space-y-0">
                                <CardTitle>Billed to</CardTitle>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link
                                        href={`mailto:${customer?.email ?? ''}`}
                                    >
                                        <Mail />
                                        {customer?.email ?? 'No email'}
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {customer ? (
                                    <div className="space-y-0.5 text-sm">
                                        <p className="font-semibold">
                                            {customer.billing_name}
                                        </p>
                                        {customer.email ? (
                                            <p className="text-muted-foreground">
                                                {customer.email}
                                            </p>
                                        ) : null}
                                        {customer.company ? (
                                            <p className="text-muted-foreground">
                                                {customer.company}
                                            </p>
                                        ) : null}
                                        {customer.tax_number ? (
                                            <p className="text-muted-foreground">
                                                Tax: {customer.tax_number}
                                            </p>
                                        ) : null}
                                        {customer.address ? (
                                            <p className="pt-1 whitespace-pre-line text-muted-foreground">
                                                {customer.address}
                                            </p>
                                        ) : null}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No customer attached.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Line items</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Description</TableHead>
                                            <TableHead className="text-right">
                                                Qty
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Unit price
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Tax
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Amount
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.items?.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <p className="font-medium">
                                                        {item.description}
                                                    </p>
                                                    {item.service_id ? (
                                                        <p className="text-xs text-muted-foreground">
                                                            Service #
                                                            {item.service_id}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell className="text-right text-muted-foreground">
                                                    {String(item.quantity)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(
                                                        item.unit_price,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right text-muted-foreground">
                                                    {item.tax_rate_percent ===
                                                        null ||
                                                    item.tax_rate_percent ===
                                                        undefined
                                                        ? `${invoice.tax_rate_percent}%`
                                                        : `${item.tax_rate_percent}%`}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatMoney(
                                                        item.total ??
                                                            item.unit_price,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        {invoice.notes || invoice.terms ? (
                            <Card>
                                <CardContent className="grid gap-4 p-4 text-sm sm:grid-cols-2">
                                    {invoice.notes ? (
                                        <div>
                                            <p className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                                                Notes
                                            </p>
                                            <p className="whitespace-pre-line text-muted-foreground">
                                                {invoice.notes}
                                            </p>
                                        </div>
                                    ) : null}
                                    {invoice.terms ? (
                                        <div>
                                            <p className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                                                Terms
                                            </p>
                                            <p className="whitespace-pre-line text-muted-foreground">
                                                {invoice.terms}
                                            </p>
                                        </div>
                                    ) : null}
                                </CardContent>
                            </Card>
                        ) : null}
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Amount</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="space-y-2 text-sm">
                                    <div className="flex justify-between text-muted-foreground">
                                        <dt>Subtotal</dt>
                                        <dd>{formatMoney(invoice.subtotal)}</dd>
                                    </div>
                                    {Number(invoice.discount_amount.cents) >
                                    0 ? (
                                        <div className="flex justify-between text-muted-foreground">
                                            <dt>Discount</dt>
                                            <dd>
                                                −
                                                {formatMoney(
                                                    invoice.discount_amount,
                                                )}
                                            </dd>
                                        </div>
                                    ) : null}
                                    <div className="flex justify-between text-muted-foreground">
                                        <dt>Tax</dt>
                                        <dd>
                                            {formatMoney(invoice.tax_total)}
                                        </dd>
                                    </div>
                                    <Separator />
                                    <div className="flex justify-between font-semibold">
                                        <dt>Total</dt>
                                        <dd>{formatMoney(invoice.total)}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="flex items-center gap-1">
                                            <BadgeCheck className="size-4" />
                                            Paid
                                        </dt>
                                        <dd>
                                            {formatMoney(invoice.amount_paid)}
                                        </dd>
                                    </div>
                                    <div
                                        className={
                                            invoice.is_overdue
                                                ? 'flex justify-between font-semibold text-red-600 dark:text-red-400'
                                                : 'flex justify-between font-semibold'
                                        }
                                    >
                                        <dt>Balance due</dt>
                                        <dd>{formatMoney(invoice.balance)}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Payments</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {invoice.payments &&
                                invoice.payments.length > 0 ? (
                                    <ul className="divide-y">
                                        {invoice.payments.map((payment) => (
                                            <li
                                                key={payment.id}
                                                className="flex items-center justify-between gap-3 p-4 text-sm"
                                            >
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-medium">
                                                            {formatMoney(
                                                                payment.amount,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                        {payment.method_label} ·{' '}
                                                        {formatDate(
                                                            payment.paid_at,
                                                        )}
                                                        {payment.reference
                                                            ? ` · ${payment.reference}`
                                                            : ''}
                                                    </p>
                                                    {payment.note ? (
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            {payment.note}
                                                        </p>
                                                    ) : null}
                                                </div>
                                                <ConfirmDeleteDialog
                                                    title="Delete payment"
                                                    description={`Remove this ${formatMoney(payment.amount)} payment from ${invoice.number}? The invoice balance will be recalculated.`}
                                                    form={PaymentController.destroy.form(
                                                        {
                                                            invoice: invoice.id,
                                                            payment: payment.id,
                                                        },
                                                    )}
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Delete payment"
                                                    >
                                                        <Trash2 className="size-4 text-destructive" />
                                                    </Button>
                                                </ConfirmDeleteDialog>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="p-4 text-sm text-muted-foreground">
                                        No payments recorded yet.
                                    </p>
                                )}
                                <div className="border-t p-4">
                                    <p className="text-xs text-muted-foreground">
                                        Status: {invoice.status_label}
                                        {invoice.sent_at
                                            ? ` · sent ${formatDate(invoice.sent_at)}`
                                            : ''}
                                        {invoice.paid_at
                                            ? ` · paid ${formatDate(invoice.paid_at)}`
                                            : ''}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

InvoicesShow.layout = (page: Props) => ({
    breadcrumbs: [
        {
            title: 'Invoices',
            href: InvoiceController.index.url(),
        },
        {
            title: page.invoice.number,
            href: InvoiceController.show.url({ invoice: page.invoice.id }),
        },
    ],
});
