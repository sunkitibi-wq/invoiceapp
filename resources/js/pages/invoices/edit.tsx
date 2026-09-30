import { Head } from '@inertiajs/react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/heading';
import { InvoiceForm } from '@/pages/invoices/form';
import type { CustomerSummary, Invoice, Option, ServiceOption } from '@/types';

type Props = {
    invoice: Invoice;
    customers: CustomerSummary[];
    services: ServiceOption[];
    discountTypes: Option[];
    statuses: Option[];
    defaults: {
        tax_rate_percent: number;
        payment_terms_days: number;
        notes: string | null;
        terms: string | null;
    };
};

export default function InvoicesEdit({
    invoice,
    customers,
    services,
    discountTypes,
    statuses,
    defaults,
}: Props) {
    return (
        <>
            <Head title={`Edit ${invoice.number}`} />

            <Heading
                title={`Edit ${invoice.number}`}
                description="Revise the line items, or move this invoice to a different status."
            />

            <InvoiceForm
                form={InvoiceController.update.form({
                    invoice: invoice.id,
                })}
                invoice={invoice}
                options={{
                    customers,
                    services,
                    discountTypes,
                    statuses,
                    defaults,
                }}
                submitLabel="Save changes"
            />
        </>
    );
}

InvoicesEdit.layout = (page: Props) => ({
    breadcrumbs: [
        {
            title: 'Invoices',
            href: InvoiceController.index.url(),
        },
        {
            title: page.invoice.number,
            href: InvoiceController.show.url({ invoice: page.invoice.id }),
        },
        {
            title: 'Edit',
            href: InvoiceController.edit.url({ invoice: page.invoice.id }),
        },
    ],
});
