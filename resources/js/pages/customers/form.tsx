import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
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
import type { Customer } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    customer?: Customer | null;
    submitLabel: string;
};

const EMPTY_VALUE = '';

export function CustomerForm({ form, customer = null, submitLabel }: Props) {
    const [country, setCountry] = useState(customer?.country ?? '');
    const [isActive, setIsActive] = useState(customer?.is_active ?? true);

    return (
        <Form {...form}>
            {({ errors, processing }) => (
                <div className="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Contact details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={customer?.name ?? EMPTY_VALUE}
                                    placeholder="Jane Cooper"
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="company">Company</Label>
                                <Input
                                    id="company"
                                    name="company"
                                    defaultValue={
                                        customer?.company ?? EMPTY_VALUE
                                    }
                                    placeholder="Acme Inc."
                                />
                                <InputError message={errors.company} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={
                                        customer?.email ?? EMPTY_VALUE
                                    }
                                    placeholder="jane@acme.test"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    defaultValue={
                                        customer?.phone ?? EMPTY_VALUE
                                    }
                                    placeholder="+1 555 0100"
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="tax_number">Tax number</Label>
                                <Input
                                    id="tax_number"
                                    name="tax_number"
                                    defaultValue={
                                        customer?.tax_number ?? EMPTY_VALUE
                                    }
                                    placeholder="VAT 123456789"
                                />
                                <InputError message={errors.tax_number} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Billing address</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="address_line_1">
                                    Address line 1
                                </Label>
                                <Input
                                    id="address_line_1"
                                    name="address_line_1"
                                    defaultValue={
                                        customer?.address_line_1 ?? EMPTY_VALUE
                                    }
                                />
                                <InputError message={errors.address_line_1} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="address_line_2">
                                    Address line 2
                                </Label>
                                <Input
                                    id="address_line_2"
                                    name="address_line_2"
                                    defaultValue={
                                        customer?.address_line_2 ?? EMPTY_VALUE
                                    }
                                />
                                <InputError message={errors.address_line_2} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="city">City</Label>
                                <Input
                                    id="city"
                                    name="city"
                                    defaultValue={customer?.city ?? EMPTY_VALUE}
                                />
                                <InputError message={errors.city} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="state">State / Province</Label>
                                <Input
                                    id="state"
                                    name="state"
                                    defaultValue={
                                        customer?.state ?? EMPTY_VALUE
                                    }
                                />
                                <InputError message={errors.state} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="postal_code">Postal code</Label>
                                <Input
                                    id="postal_code"
                                    name="postal_code"
                                    defaultValue={
                                        customer?.postal_code ?? EMPTY_VALUE
                                    }
                                />
                                <InputError message={errors.postal_code} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="country">Country</Label>
                                <Input
                                    id="country"
                                    name="country"
                                    value={country}
                                    maxLength={2}
                                    onChange={(event) =>
                                        setCountry(
                                            event.target.value.toUpperCase(),
                                        )
                                    }
                                    placeholder="US"
                                />
                                <InputError message={errors.country} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Additional information</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={
                                        customer?.notes ?? EMPTY_VALUE
                                    }
                                    placeholder="Internal notes about this customer…"
                                    className="field-sizing-content min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive"
                                />
                                <InputError message={errors.notes} />
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
                                Active customer
                            </label>
                            <InputError message={errors.is_active} />
                        </CardContent>
                        <CardFooter className="justify-end gap-3">
                            <Button variant="ghost" type="button" asChild>
                                <Link href={CustomerController.index.url()}>
                                    Cancel
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : submitLabel}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            )}
        </Form>
    );
}
