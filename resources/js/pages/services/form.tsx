import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import ServiceController from '@/actions/App/Http/Controllers/ServiceController';
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
import { percentToInput } from '@/lib/format';
import type { Service } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    service?: Service | null;
    submitLabel: string;
};

const EMPTY_VALUE = '';

export function ServiceForm({ form, service = null, submitLabel }: Props) {
    const [isActive, setIsActive] = useState(service?.is_active ?? true);

    return (
        <Form {...form}>
            {({ errors, processing }) => (
                <Card>
                    <CardHeader>
                        <CardTitle>Service details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={service?.name ?? EMPTY_VALUE}
                                placeholder="Consulting"
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="unit">Unit</Label>
                            <Input
                                id="unit"
                                name="unit"
                                defaultValue={service?.unit ?? EMPTY_VALUE}
                                placeholder="hour"
                            />
                            <InputError message={errors.unit} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="unit_price">Unit price</Label>
                            <Input
                                id="unit_price"
                                name="unit_price"
                                type="text"
                                inputMode="decimal"
                                defaultValue={
                                    service
                                        ? (
                                              service.unit_price.cents / 100
                                          ).toFixed(2)
                                        : EMPTY_VALUE
                                }
                                placeholder="125.00"
                            />
                            <InputError message={errors.unit_price} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="tax_rate_percent">
                                Tax rate (%)
                            </Label>
                            <Input
                                id="tax_rate_percent"
                                name="tax_rate_percent"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                defaultValue={percentToInput(
                                    service?.tax_rate === null ||
                                        service?.tax_rate === undefined
                                        ? null
                                        : service.tax_rate * 100,
                                )}
                                placeholder="20"
                            />
                            <InputError message={errors.tax_rate_percent} />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="description">Description</Label>
                            <textarea
                                id="description"
                                name="description"
                                defaultValue={
                                    service?.description ?? EMPTY_VALUE
                                }
                                placeholder="What is this service?"
                                className="field-sizing-content min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive"
                            />
                            <InputError message={errors.description} />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="hidden"
                                name="is_active"
                                value={isActive ? '1' : '0'}
                            />
                            <Checkbox
                                checked={isActive}
                                onCheckedChange={(checked) =>
                                    setIsActive(checked === true)
                                }
                            />
                            Active service
                        </label>
                        <InputError message={errors.is_active} />
                    </CardContent>
                    <CardFooter className="justify-end gap-3">
                        <Button variant="ghost" type="button" asChild>
                            <Link href={ServiceController.index.url()}>
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
