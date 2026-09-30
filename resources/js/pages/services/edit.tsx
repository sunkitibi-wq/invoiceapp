import { Head } from '@inertiajs/react';
import ServiceController from '@/actions/App/Http/Controllers/ServiceController';
import Heading from '@/components/heading';
import { ServiceForm } from '@/pages/services/form';
import type { Service } from '@/types';

type Props = {
    service: Service;
};

export default function ServicesEdit({ service }: Props) {
    return (
        <>
            <Head title={`Edit ${service.name}`} />

            <Heading
                title={`Edit ${service.name}`}
                description="Update this service's details."
            />

            <ServiceForm
                form={ServiceController.update.form({
                    service: service.id,
                })}
                service={service}
                submitLabel="Save changes"
            />
        </>
    );
}

ServicesEdit.layout = (page: Props) => ({
    breadcrumbs: [
        {
            title: 'Services',
            href: ServiceController.index.url(),
        },
        {
            title: 'Edit',
            href: ServiceController.edit.url({ service: page.service.id }),
        },
    ],
});
