import { Head } from '@inertiajs/react';
import PermissionController from '@/actions/App/Http/Controllers/PermissionController';
import Heading from '@/components/heading';
import { PermissionForm } from '@/pages/permissions/form';
import type { PermissionRecord } from '@/types';

type Props = {
    permission: PermissionRecord & { id: number };
};

export default function PermissionsEdit({ permission }: Props) {
    return (
        <>
            <Head title={`Edit ${permission.name}`} />

            <Heading
                title={`Edit ${permission.name}`}
                description={`Currently reads as “${permission.label}”.`}
            />

            <PermissionForm
                form={PermissionController.update.form({
                    permission: permission.id,
                })}
                name={permission.name}
                submitLabel="Save changes"
            />
        </>
    );
}

PermissionsEdit.layout = (page: Props) => ({
    breadcrumbs: [
        {
            title: 'Permissions',
            href: PermissionController.index.url(),
        },
        {
            title: 'Edit',
            href: PermissionController.edit.url({
                permission: page.permission.id,
            }),
        },
    ],
});
