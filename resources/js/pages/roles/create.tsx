import { Head } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import { RoleForm } from '@/pages/roles/form';
import type { PermissionGroup } from '@/types';

type Props = {
    role: null;
    permissionGroups: PermissionGroup[];
};

export default function RolesCreate({ permissionGroups }: Props) {
    return (
        <>
            <Head title="New role" />

            <Heading
                title="New role"
                description="Name this role, then pick what it may do."
            />

            <RoleForm
                form={RoleController.store.form()}
                permissionGroups={permissionGroups}
                submitLabel="Create role"
            />
        </>
    );
}

RolesCreate.layout = {
    breadcrumbs: [
        { title: 'Roles', href: RoleController.index.url() },
        { title: 'New', href: RoleController.create.url() },
    ],
};
