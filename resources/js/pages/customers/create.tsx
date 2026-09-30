import { Head } from '@inertiajs/react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import Heading from '@/components/heading';
import { CustomerForm } from '@/pages/customers/form';

export default function CustomersCreate() {
    return (
        <>
            <Head title="New customer" />

            <Heading
                title="New customer"
                description="Add a customer to start invoicing them."
            />

            <CustomerForm
                form={CustomerController.store.form()}
                submitLabel="Create customer"
            />
        </>
    );
}

CustomersCreate.layout = {
    breadcrumbs: [
        {
            title: 'Customers',
            href: CustomerController.index.url(),
        },
        {
            title: 'New customer',
            href: CustomerController.create.url(),
        },
    ],
};
