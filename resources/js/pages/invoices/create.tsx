import { Head } from '@inertiajs/react';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/heading';
import { InvoiceForm } from '@/pages/invoices/form';
import type { CustomerSummary, Option, ServiceOption } from '@/types';

type Props = {
    invoice: null;
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

export default function InvoicesCreate({
    customers,
    services,
    discountTypes,
    statuses,
    defaults,
}: Props) {
    return (
        <>
            <Head title="New invoice" />

            <Heading
                title="New invoice"
                description="Create an invoice for a customer."
            />

            <InvoiceForm
                form={InvoiceController.store.form()}
                options={{
                    customers,
                    services,
                    discountTypes,
                    statuses,
                    defaults,
                }}
                submitLabel="Create invoice"
            />
        </>
    );
}

InvoicesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Invoices',
            href: InvoiceController.index.url(),
        },
        {
            title: 'New invoice',
            href: InvoiceController.create.url(),
        },
    ],
};
