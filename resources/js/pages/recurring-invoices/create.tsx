import { Head } from '@inertiajs/react';
import RecurringInvoiceController from '@/actions/App/Http/Controllers/RecurringInvoiceController';
import Heading from '@/components/heading';
import { RecurringInvoiceForm } from '@/pages/recurring-invoices/form';
import type { Option, ServiceOption } from '@/types';

type Props = {
    recurringInvoice: null;
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

export default function RecurringInvoicesCreate({
    customers,
    services,
    frequencies,
    discountTypes,
    defaults,
}: Props) {
    return (
        <>
            <Head title="New recurring invoice" />

            <Heading
                title="New recurring invoice"
                description="Schedule invoices to be created automatically."
            />

            <RecurringInvoiceForm
                form={RecurringInvoiceController.store.form()}
                options={{
                    customers,
                    services,
                    frequencies,
                    discountTypes,
                    defaults,
                }}
                submitLabel="Create schedule"
            />
        </>
    );
}

RecurringInvoicesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Recurring Invoices',
            href: RecurringInvoiceController.index.url(),
        },
        {
            title: 'New schedule',
            href: RecurringInvoiceController.create.url(),
        },
    ],
};
