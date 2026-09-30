import { Head } from '@inertiajs/react';
import RecurringInvoiceController from '@/actions/App/Http/Controllers/RecurringInvoiceController';
import Heading from '@/components/heading';
import { RecurringInvoiceForm } from '@/pages/recurring-invoices/form';
import type { Option, RecurringInvoice, ServiceOption } from '@/types';

type Props = {
    recurringInvoice: RecurringInvoice;
    customers: Array<{ id: number; name: string; company: string | null }>;
    services: ServiceOption[];
    frequencies: Option[];
    discountTypes: Option[];
    defaults: {
        tax_rate_percent: number;
        due_date_days: number;
        notes: string | null;
        terms: string | null;
    };
};

export default function RecurringInvoicesEdit({
    recurringInvoice,
    customers,
    services,
    frequencies,
    discountTypes,
    defaults,
}: Props) {
    return (
        <>
            <Head title="Edit recurring invoice" />

            <Heading
                title={`Edit ${recurringInvoice.customer?.billing_name ?? 'schedule'}`}
                description="Changes apply to future invoices only."
            />

            <RecurringInvoiceForm
                form={RecurringInvoiceController.update.form({
                    recurring_invoice: recurringInvoice.id,
                })}
                recurringInvoice={recurringInvoice}
                options={{
                    customers,
                    services,
                    frequencies,
                    discountTypes,
                    defaults,
                }}
                submitLabel="Save changes"
            />
        </>
    );
}

RecurringInvoicesEdit.layout = (page: Props) => ({
    breadcrumbs: [
        {
            title: 'Recurring Invoices',
            href: RecurringInvoiceController.index.url(),
        },
        {
            title: 'Edit',
            href: RecurringInvoiceController.edit.url({
                recurring_invoice: page.recurringInvoice.id,
            }),
        },
    ],
});
