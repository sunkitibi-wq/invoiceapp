import { Form, Link } from '@inertiajs/react';
import PermissionController from '@/actions/App/Http/Controllers/PermissionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    name?: string;
    submitLabel: string;
};

export function PermissionForm({ form, name = '', submitLabel }: Props) {
    return (
        <Form {...form}>
            {({ errors, processing }) => (
                <Card>
                    <CardHeader>
                        <CardTitle>Permission details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={name}
                                placeholder="invoices.send"
                                className="font-mono"
                            />
                            <InputError message={errors.name} />
                            <p className="text-xs text-muted-foreground">
                                Written as a resource and an action. The section
                                and the wording are worked out from it.
                            </p>
                        </div>
                    </CardContent>
                    <CardFooter className="justify-end gap-3">
                        <Button variant="ghost" type="button" asChild>
                            <Link href={PermissionController.index.url()}>
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
