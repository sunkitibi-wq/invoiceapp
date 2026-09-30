import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PermissionGroup, Role } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    role?: Role | null;
    permissionGroups: PermissionGroup[];
    submitLabel: string;
};

const EMPTY_VALUE = '';

export function RoleForm({
    form,
    role = null,
    permissionGroups,
    submitLabel,
}: Props) {
    const [selected, setSelected] = useState<string[]>(role?.permissions ?? []);

    const toggle = (value: string, checked: boolean) => {
        setSelected((current) =>
            checked
                ? [...current, value]
                : current.filter((permission) => permission !== value),
        );
    };

    /**
     * Selecting every action in a group is the common case, so the header
     * checkbox saves picking each one out by hand.
     */
    const toggleGroup = (values: string[], checked: boolean) => {
        setSelected((current) => {
            const rest = current.filter(
                (permission) => !values.includes(permission),
            );

            return checked ? [...rest, ...values] : rest;
        });
    };

    return (
        <Form {...form}>
            {({ errors, processing }) => (
                <Card>
                    <CardHeader>
                        <CardTitle>Role details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={role?.name ?? EMPTY_VALUE}
                                placeholder="Accountant"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-3">
                            <Label>Permissions</Label>
                            {selected.map((permission) => (
                                <input
                                    key={permission}
                                    type="hidden"
                                    name="permissions[]"
                                    value={permission}
                                />
                            ))}

                            {permissionGroups.map((group) => {
                                const values = group.permissions.map(
                                    (permission) => permission.value,
                                );
                                const granted = values.filter((value) =>
                                    selected.includes(value),
                                ).length;

                                return (
                                    <div
                                        key={group.group}
                                        className="rounded-md border"
                                    >
                                        <div className="flex items-center gap-2 border-b bg-muted/40 px-4 py-3">
                                            <Checkbox
                                                checked={
                                                    granted === values.length
                                                }
                                                onCheckedChange={(checked) =>
                                                    toggleGroup(
                                                        values,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <span className="text-sm font-medium">
                                                {group.group}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {granted}/{values.length}
                                            </span>
                                        </div>
                                        <div className="grid gap-3 p-4 sm:grid-cols-2">
                                            {group.permissions.map(
                                                (permission) => (
                                                    <label
                                                        key={permission.value}
                                                        className="flex items-center gap-2 text-sm"
                                                    >
                                                        <Checkbox
                                                            checked={selected.includes(
                                                                permission.value,
                                                            )}
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                toggle(
                                                                    permission.value,
                                                                    checked ===
                                                                        true,
                                                                )
                                                            }
                                                        />
                                                        {permission.label}
                                                    </label>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                );
                            })}

                            <InputError message={errors.permissions} />
                        </div>
                    </CardContent>
                    <CardFooter className="justify-end gap-3">
                        <Button variant="ghost" type="button" asChild>
                            <Link href={RoleController.index.url()}>
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving…' : submitLabel}
                        </Button>
                    </CardFooter>
                </Card>
            )}
        </Form>
    );
}
