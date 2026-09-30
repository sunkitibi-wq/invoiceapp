import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, UserCog } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { DataTableSearch } from '@/components/data-table/data-table-search';
import { DataTableSortButton } from '@/components/data-table/data-table-sort-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { ManagedUser, Paginated, RoleOption } from '@/types';

type Props = {
    users: Paginated<ManagedUser>;
    filters: {
        search: string | null;
    };
    roles: RoleOption[];
};

type SortProps = {
    sort: string | null;
    direction: 'asc' | 'desc' | null;
};

type PageProps = Props & {
    sort?: string | null;
    direction?: 'asc' | 'desc' | null;
};

export default function UsersIndex({
    users,
    filters,
    roles,
    sort = 'name',
    direction = 'asc',
}: PageProps) {
    const sortable: SortProps = { sort, direction };

    const roleName = (id: number) =>
        roles.find((role) => role.value === id)?.label ?? 'Unknown';

    return (
        <>
            <Head title="Users" />

            <div className="space-y-6">
                <div className="flex items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Users"
                        description="Who can sign in, and what each of them may do."
                    />
                    <Button asChild>
                        <Link href={UserController.create.url()}>
                            <Plus />
                            New user
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between gap-4 space-y-0">
                        <div className="flex items-center gap-2">
                            <UserCog className="size-4 text-muted-foreground" />
                            <span className="text-sm text-muted-foreground">
                                {users.total} user
                                {users.total === 1 ? '' : 's'}
                            </span>
                        </div>
                        <DataTableSearch
                            route={UserController.index.url()}
                            defaultValue={filters.search ?? ''}
                            placeholder="Search users…"
                            className="w-full sm:w-64"
                        />
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Name"
                                            sortKey="name"
                                            sort={sortable.sort ?? ''}
                                            direction={
                                                sortable.direction ?? 'asc'
                                            }
                                            route={UserController.index.url()}
                                        />
                                    </TableHead>
                                    <TableHead>
                                        <DataTableSortButton
                                            label="Email"
                                            sortKey="email"
                                            sort={sortable.sort ?? ''}
                                            direction={
                                                sortable.direction ?? 'asc'
                                            }
                                            route={UserController.index.url()}
                                        />
                                    </TableHead>
                                    <TableHead>Roles</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No users found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    users.data.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell className="font-medium">
                                                {user.name}
                                            </TableCell>
                                            <TableCell>{user.email}</TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {user.roles &&
                                                    user.roles.length > 0 ? (
                                                        user.roles.map(
                                                            (role) => (
                                                                <Badge
                                                                    key={
                                                                        role.value
                                                                    }
                                                                    variant="secondary"
                                                                >
                                                                    {roleName(
                                                                        role.value,
                                                                    )}
                                                                </Badge>
                                                            ),
                                                        )
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            None
                                                        </span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {user.is_admin ? (
                                                        <Badge>Superuser</Badge>
                                                    ) : null}
                                                    {user.email_verified_at ? (
                                                        <Badge variant="outline">
                                                            Verified
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="destructive">
                                                            Unverified
                                                        </Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Edit user"
                                                    >
                                                        <Link
                                                            href={UserController.edit.url(
                                                                {
                                                                    user: user.id,
                                                                },
                                                            )}
                                                            preserveScroll
                                                        >
                                                            <Pencil />
                                                        </Link>
                                                    </Button>
                                                    <ConfirmDeleteDialog
                                                        title="Delete user"
                                                        description={`Remove ${user.name}? They will no longer be able to sign in.`}
                                                        form={UserController.destroy.form(
                                                            {
                                                                user: user.id,
                                                            },
                                                        )}
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title="Delete user"
                                                        >
                                                            <Trash2 className="text-destructive" />
                                                        </Button>
                                                    </ConfirmDeleteDialog>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        <DataTablePagination pagination={users} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Users', href: UserController.index.url() }],
};
