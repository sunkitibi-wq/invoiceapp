import { Head } from '@inertiajs/react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import Heading from '@/components/heading';
import { CustomerForm } from '@/pages/customers/form';
import type { Customer } from '@/types';

type Props = {
    customer: Customer;
};

export default function CustomersEdit({ customer }: Props) {
    return (
        <>
            <Head title={`Edit ${customer.name}`} />

            <Heading
                title={`Edit ${customer.name}`}
                description="Update this customer's details."
            />

            <CustomerForm
                form={CustomerController.update.form({
                    customer: customer.id,
                })}
                customer={customer}
                submitLabel="Save changes"
            />
        </>
    );
}

CustomersEdit.layout = (page: Props) => ({
    breadcrumbs: [
        {
            title: 'Customers',
            href: CustomerController.index.url(),
        },
        {
            title: 'Edit',
            href: CustomerController.edit.url({
                customer: page.customer.id,
            }),
        },
    ],
});
