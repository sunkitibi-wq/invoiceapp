import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, KeyRound, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import PermissionController from '@/actions/App/Http/Controllers/PermissionController';
import { ConfirmDeleteDialog } from '@/components/data-table/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { PermissionRecord, PermissionSummary } from '@/types';

type Props = {
    permissions: PermissionRecord[];
    summary: PermissionSummary;
    filters: { search: string | null };
};

const STATUS_LABEL: Record<PermissionRecord['status'], string> = {
    synced: 'In use',
    custom: 'Custom',
    missing: 'Not seeded',
};

export default function PermissionsIndex({
    permissions,
    summary,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch = (value: string) => {
        setSearch(value);

        router.get(
            PermissionController.index.url(),
            { search: value === '' ? undefined : value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Permissions" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        className="mb-0"
                        title="Permissions"
                        description="Every ability a role can grant."
                    />
                    <Button asChild>
                        <Link href={PermissionController.create.url()}>
                            <Plus />
                            New permission
                        </Link>
                    </Button>
                </div>

                {summary.missing > 0 ? (
                    <div className="flex items-start gap-3 rounded-md border border-destructive/40 bg-destructive/5 p-4 text-sm">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0 text-destructive" />
                        <p>
                            {summary.missing}{' '}
                            {summary.missing === 1
                                ? 'permission is'
                                : 'permissions are'}{' '}
                            used by the application but not in the database. Run{' '}
                            <code className="font-mono">
                                php artisan db:seed --class=RolePermissionSeeder
                            </code>{' '}
                            or anyone relying on them will be denied.
                        </p>
                    </div>
                ) : null}

                <div className="grid gap-4 sm:grid-cols-4">
                    {(
                        [
                            ['Total', summary.total],
                            ['In use', summary.synced],
                            ['Custom', summary.custom],
                            ['Not seeded', summary.missing],
                        ] as const
                    ).map(([label, value]) => (
                        <Card key={label}>
                            <CardContent className="pt-6">
                                <p className="text-sm text-muted-foreground">
                                    {label}
                                </p>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="max-w-sm">
                    <Input
                        value={search}
                        onChange={(event) => submitSearch(event.target.value)}
                        placeholder="Search permissions…"
                        aria-label="Search permissions"
                    />
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Permission</TableHead>
                                    <TableHead>Section</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Roles</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {permissions.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            No permissions match that search.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    permissions.map((permission) => (
                                        <TableRow key={permission.name}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {permission.label}
                                                </div>
                                                <code className="text-xs text-muted-foreground">
                                                    {permission.name}
                                                </code>
                                            </TableCell>
                                            <TableCell>
                                                {permission.group}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        permission.status ===
                                                        'missing'
                                                            ? 'destructive'
                                                            : permission.status ===
                                                                'custom'
                                                              ? 'secondary'
                                                              : 'outline'
                                                    }
                                                >
                                                    {
                                                        STATUS_LABEL[
                                                            permission.status
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {permission.roles.length ===
                                                0 ? (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                ) : (
                                                    <div className="flex flex-wrap gap-1">
                                                        {permission.roles.map(
                                                            (role) => (
                                                                <Badge
                                                                    key={
                                                                        role.id
                                                                    }
                                                                    variant="secondary"
                                                                >
                                                                    {role.name}
                                                                </Badge>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    {permission.id &&
                                                    !permission.is_code_defined ? (
                                                        <>
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="icon"
                                                                title="Edit permission"
                                                            >
                                                                <Link
                                                                    href={PermissionController.edit.url(
                                                                        {
                                                                            permission:
                                                                                permission.id,
                                                                        },
                                                                    )}
                                                                    preserveScroll
                                                                >
                                                                    <Pencil />
                                                                </Link>
                                                            </Button>
                                                            <ConfirmDeleteDialog
                                                                title="Delete permission"
                                                                confirmLabel="Delete"
                                                                description={`Delete ${permission.name}? It is removed from every role that holds it.`}
                                                                form={PermissionController.destroy.form(
                                                                    {
                                                                        permission:
                                                                            permission.id,
                                                                    },
                                                                )}
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    title="Delete permission"
                                                                >
                                                                    <Trash2 className="text-destructive" />
                                                                </Button>
                                                            </ConfirmDeleteDialog>
                                                        </>
                                                    ) : (
                                                        <span
                                                            className="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                                            title="Used by the application itself, so it cannot be renamed or deleted here."
                                                        >
                                                            <KeyRound className="size-3" />
                                                            Managed in code
                                                        </span>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

PermissionsIndex.layout = {
    breadcrumbs: [
        { title: 'Permissions', href: PermissionController.index.url() },
    ],
};
