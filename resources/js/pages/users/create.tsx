import { Head } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { UserForm } from '@/pages/users/form';
import type { RoleOption } from '@/types';

type Props = {
    user: null;
    roles: RoleOption[];
};

export default function UsersCreate({ roles }: Props) {
    return (
        <>
            <Head title="New user" />

            <Heading
                title="New user"
                description="Create an account and choose what it may do."
            />

            <UserForm
                form={UserController.store.form()}
                roles={roles}
                submitLabel="Create user"
            />
        </>
    );
}

UsersCreate.layout = {
    breadcrumbs: [
        { title: 'Users', href: UserController.index.url() },
        { title: 'New', href: UserController.create.url() },
    ],
};
