import { Head } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { UserForm } from '@/pages/users/form';
import type { ManagedUser, RoleOption } from '@/types';

type Props = {
    user: ManagedUser;
    roles: RoleOption[];
};

export default function UsersEdit({ user, roles }: Props) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <Heading
                title={`Edit ${user.name}`}
                description="Update this account's details and roles."
            />

            <UserForm
                form={UserController.update.form({ user: user.id })}
                user={user}
                roles={roles}
                submitLabel="Save changes"
            />
        </>
    );
}

UsersEdit.layout = (page: Props) => ({
    breadcrumbs: [
        { title: 'Users', href: UserController.index.url() },
        {
            title: 'Edit',
            href: UserController.edit.url({ user: page.user.id }),
        },
    ],
});
