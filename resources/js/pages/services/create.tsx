import { Head } from '@inertiajs/react';
import ServiceController from '@/actions/App/Http/Controllers/ServiceController';
import Heading from '@/components/heading';
import { ServiceForm } from '@/pages/services/form';

export default function ServicesCreate() {
    return (
        <>
            <Head title="New service" />

            <Heading
                title="New service"
                description="Add a service you can include on invoices."
            />

            <ServiceForm
                form={ServiceController.store.form()}
                submitLabel="Create service"
            />
        </>
    );
}

ServicesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Services',
            href: ServiceController.index.url(),
        },
        {
            title: 'New service',
            href: ServiceController.create.url(),
        },
    ],
};
