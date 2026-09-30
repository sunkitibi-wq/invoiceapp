import { Head } from '@inertiajs/react';
import PermissionController from '@/actions/App/Http/Controllers/PermissionController';
import Heading from '@/components/heading';
import { PermissionForm } from '@/pages/permissions/form';

export default function PermissionsCreate() {
    return (
        <>
            <Head title="New permission" />

            <Heading
                title="New permission"
                description="Add an ability that a role can then grant."
            />

            <PermissionForm
                form={PermissionController.store.form()}
                submitLabel="Create permission"
            />
        </>
    );
}

PermissionsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Permissions',
            href: PermissionController.index.url(),
        },
        {
            title: 'New',
            href: PermissionController.create.url(),
        },
    ],
};
