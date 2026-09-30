import { Head } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import { RoleForm } from '@/pages/roles/form';
import type { PermissionGroup, Role } from '@/types';

type Props = {
    role: Role;
    permissionGroups: PermissionGroup[];
};

export default function RolesEdit({ role, permissionGroups }: Props) {
    return (
        <>
            <Head title={`Edit ${role.name}`} />

            <Heading
                title={`Edit ${role.name}`}
                description="Change what members of this role may do."
            />

            <RoleForm
                form={RoleController.update.form({ role: role.id })}
                role={role}
                permissionGroups={permissionGroups}
                submitLabel="Save changes"
            />
        </>
    );
}

RolesEdit.layout = (page: Props) => ({
    breadcrumbs: [
        { title: 'Roles', href: RoleController.index.url() },
        {
            title: 'Edit',
            href: RoleController.edit.url({ role: page.role.id }),
        },
    ],
});
