import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserController';
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
import type { ManagedUser, RoleOption } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    user?: ManagedUser | null;
    roles: RoleOption[];
    submitLabel: string;
};

const EMPTY_VALUE = '';

export function UserForm({ form, user = null, roles, submitLabel }: Props) {
    const [selected, setSelected] = useState<number[]>(
        user?.roles?.map((role) => role.value) ?? [],
    );

    const toggle = (value: number, checked: boolean) => {
        setSelected((current) =>
            checked
                ? [...current, value]
                : current.filter((id) => id !== value),
        );
    };

    return (
        <Form {...form}>
            {({ errors, processing }) => (
                <Card>
                    <CardHeader>
                        <CardTitle>Account details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={user?.name ?? EMPTY_VALUE}
                                placeholder="Adaeze Okafor"
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={user?.email ?? EMPTY_VALUE}
                                placeholder="adaeze@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {user ? 'New password' : 'Password'}
                            </Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                placeholder={
                                    user
                                        ? 'Leave blank to keep the current password'
                                        : 'password'
                                }
                            />
                            <InputError message={errors.password} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <div className="grid gap-3 sm:col-span-2">
                            <Label>Roles</Label>
                            {selected.map((id) => (
                                <input
                                    key={id}
                                    type="hidden"
                                    name="roles[]"
                                    value={id}
                                />
                            ))}
                            {roles.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No roles have been created yet.
                                </p>
                            ) : (
                                <div className="grid gap-3 rounded-md border p-4 sm:grid-cols-2">
                                    {roles.map((role) => (
                                        <label
                                            key={role.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={selected.includes(
                                                    role.value,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggle(
                                                        role.value,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            {role.label}
                                        </label>
                                    ))}
                                </div>
                            )}
                            <InputError message={errors.roles} />
                        </div>
                    </CardContent>
                    <CardFooter className="justify-end gap-3">
                        <Button variant="ghost" type="button" asChild>
                            <Link href={UserController.index.url()}>
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
