import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { PermissionGroup, Role } from '@/types';

type Props = {
    roles: Role[];
    permissionGroups: PermissionGroup[];
};

export default function RolesIndex({ roles, permissionGroups }: Props) {
    const labelFor = (value: string) => {
        for (const group of permissionGroups) {
            const match = group.permissions.find(
                (permission) => permission.value === value,
            );

            if (match) {
                return match.label;
            }
        }

        return value;
    };

    return (
        <>
            <Head title="Roles" />

            <div className="space-y-6">
                <div className="flex items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Roles"
                        description="Group permissions together, then assign them to users."
                    />
                    <Button asChild>
                        <Link href={RoleController.create.url()}>
                            <Plus />
                            New role
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Permissions</TableHead>
                                    <TableHead className="text-right">
                                        Users
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No roles found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    roles.map((role) => (
                                        <TableRow key={role.id}>
                                            <TableCell className="font-medium">
                                                {role.name}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {role.permissions.length ===
                                                    0 ? (
                                                        <span className="text-muted-foreground">
                                                            None
                                                        </span>
                                                    ) : (
                                                        role.permissions.map(
                                                            (permission) => (
                                                                <Badge
                                                                    key={
                                                                        permission
                                                                    }
                                                                    variant="secondary"
                                                                >
                                                                    {labelFor(
                                                                        permission,
                                                                    )}
                                                                </Badge>
                                                            ),
                                                        )
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {role.users_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Edit role"
                                                    >
                                                        <Link
                                                            href={RoleController.edit.url(
                                                                {
                                                                    role: role.id,
                                                                },
                                                            )}
                                                            preserveScroll
                                                        >
                                                            <Pencil />
                                                        </Link>
                                                    </Button>
                                                    <ConfirmDeleteDialog
                                                        title="Delete role"
                                                        description={`Delete ${role.name}? Anyone holding it loses the permissions it grants.`}
                                                        form={RoleController.destroy.form(
                                                            {
                                                                role: role.id,
                                                            },
                                                        )}
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            title="Delete role"
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
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <ShieldCheck className="size-4" />
                            <span>
                                {permissionGroups.length} permission groups,
                                totalling{' '}
                                {permissionGroups.reduce(
                                    (total, group) =>
                                        total + group.permissions.length,
                                    0,
                                )}{' '}
                                permissions.
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'Roles', href: RoleController.index.url() }],
};
