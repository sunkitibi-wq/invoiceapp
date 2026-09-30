import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Calculator, Link2, PlusCircle, Trash2 } from 'lucide-react';
import type { Errors } from '@inertiajs/core';
import RecurringInvoiceController from '@/actions/App/Http/Controllers/RecurringInvoiceController';
import InputError from '@/components/input-error';
import { useMoneyFormatter } from '@/hooks/use-money-formatter';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import type { RecurringInvoice, ServiceOption } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type LineItemData = {
    service_id: string;
    description: string;
    quantity: string;
    unit_price: string;
    tax_rate_percent: string;
};

type FormData = {
    customer_id: string;
    frequency: string;
    next_invoice_at: string;
    end_at: string;
    due_date_days: string;
    tax_rate_percent: string;
    discount_type: string;
    discount_value: string;
    notes: string;
    terms: string;
    is_active: boolean;
    items: LineItemData[];
};

type FormOptions = {
    customers: Array<{ id: number; name: string; company: string | null }>;
    services: ServiceOption[];
    frequencies: Array<{ value: string; label: string }>;
    discountTypes: Array<{ value: string; label: string }>;
    defaults: {
        tax_rate_percent: number;
        due_date_days: number;
        notes: string | null;
        terms: string | null;
    };
};

type Props = {
    form: RouteFormDefinition<'post'>;
    recurringInvoice?: RecurringInvoice | null;
    options: FormOptions;
    submitLabel: string;
};

function toDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function getError(errors: Errors, key: string): string | undefined {
    const direct = errors[key] as unknown;

    if (typeof direct === 'string') {
        return direct;
    }

    const parts = key.split('.');
    let current: unknown = errors;

    for (const part of parts) {
        if (
            current !== null &&
            typeof current === 'object' &&
            part in current
        ) {
            current = (current as Record<string, unknown>)[part];
        } else {
            return undefined;
        }
    }

    return typeof current === 'string' ? current : undefined;
}

function toCents(value: string): number {
    const parsed = Number(value.trim().replace(',', '.'));

    return Number.isNaN(parsed) || parsed < 0 ? 0 : Math.round(parsed * 100);
}

function emptyLine(): LineItemData {
    return {
        service_id: '',
        description: '',
        quantity: '1',
        unit_price: '',
        tax_rate_percent: '',
    };
}

function buildInitialData(
    recurringInvoice: RecurringInvoice | null | undefined,
    options: FormOptions,
): FormData {
    if (!recurringInvoice) {
        return {
            customer_id: '',
            frequency: '',
            next_invoice_at: toDateInput(new Date()),
            end_at: '',
            due_date_days: String(options.defaults.due_date_days),
            tax_rate_percent: String(options.defaults.tax_rate_percent),
            discount_type: '',
            discount_value: '',
            notes: options.defaults.notes ?? '',
            terms: options.defaults.terms ?? '',
            is_active: true,
            items: [emptyLine()],
        };
    }

    return {
        customer_id: String(recurringInvoice.customer_id),
        frequency: recurringInvoice.frequency,
        next_invoice_at: recurringInvoice.next_invoice_at,
        end_at: recurringInvoice.end_at ?? '',
        due_date_days: String(recurringInvoice.due_date_days),
        tax_rate_percent: String(recurringInvoice.tax_rate_percent),
        discount_type: recurringInvoice.discount_type ?? '',
        discount_value:
            recurringInvoice.discount_value === null ||
            recurringInvoice.discount_value === undefined
                ? ''
                : String(recurringInvoice.discount_value),
        notes: recurringInvoice.notes ?? '',
        terms: recurringInvoice.terms ?? '',
        is_active: recurringInvoice.is_active,
        items:
            recurringInvoice.items && recurringInvoice.items.length > 0
                ? recurringInvoice.items.map((item) => ({
                      service_id: item.service_id
                          ? String(item.service_id)
                          : '',
                      description: item.description,
                      quantity: String(item.quantity),
                      unit_price: (item.unit_price.cents / 100).toFixed(2),
                      tax_rate_percent:
                          item.tax_rate_percent === null ||
                          item.tax_rate_percent === undefined
                              ? ''
                              : String(item.tax_rate_percent),
                  }))
                : [emptyLine()],
    };
}

export function RecurringInvoiceForm({
    form,
    recurringInvoice = null,
    options,
    submitLabel,
}: Props) {
    const {
        data: formData,
        setData,
        errors,
        processing,
        post,
    } = useForm<FormData>(buildInitialData(recurringInvoice, options));

    const formatCents = useMoneyFormatter();

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(form.action);
    };

    const updateLine = (index: number, patch: Partial<LineItemData>) => {
        setData(
            'items',
            formData.items.map((item, i) =>
                i === index ? { ...item, ...patch } : item,
            ),
        );
    };

    const addLine = () => {
        setData('items', [...formData.items, emptyLine()]);
    };

    const removeLine = (index: number) => {
        setData(
            'items',
            formData.items.filter((_, i) => i !== index),
        );
    };

    const selectService = (index: number, serviceId: string) => {
        const service = options.services.find(
            (candidate) => String(candidate.id) === serviceId,
        );

        if (!service) {
            updateLine(index, { service_id: serviceId });
            return;
        }

        updateLine(index, {
            service_id: serviceId,
            description: service.description ?? service.name,
            unit_price: service.unit_price,
            tax_rate_percent:
                service.tax_rate_percent === null
                    ? ''
                    : String(service.tax_rate_percent),
        });
    };

    const subtotal = formData.items.reduce(
        (sum, item) =>
            sum + toCents(item.unit_price) * Number(item.quantity || 0),
        0,
    );

    const invoiceTaxPercent = toCents(formData.tax_rate_percent) / 100;

    const taxTotal = formData.items.reduce((sum, item) => {
        const linePercent =
            item.tax_rate_percent === ''
                ? invoiceTaxPercent
                : toCents(item.tax_rate_percent) / 100;
        const lineSubtotal =
            toCents(item.unit_price) * Number(item.quantity || 0);

        return sum + lineSubtotal * (linePercent / 100);
    }, 0);

    const discount =
        formData.discount_type === 'percent'
            ? subtotal * (toCents(formData.discount_value) / 10000)
            : toCents(formData.discount_value);

    const total = Math.max(0, Math.round(subtotal + taxTotal - discount));

    return (
        <form
            onSubmit={handleSubmit}
            noValidate
            className="flex flex-col gap-6"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Schedule</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="customer_id">Customer</Label>
                        <Select
                            value={formData.customer_id}
                            onValueChange={(value) =>
                                setData('customer_id', value)
                            }
                        >
                            <SelectTrigger id="customer_id">
                                <SelectValue placeholder="Select a customer…" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.customers.map((customer) => (
                                    <SelectItem
                                        key={customer.id}
                                        value={String(customer.id)}
                                    >
                                        {customer.name}
                                        {customer.company
                                            ? ` · ${customer.company}`
                                            : ''}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.customer_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="frequency">Frequency</Label>
                        <Select
                            value={formData.frequency}
                            onValueChange={(value) =>
                                setData('frequency', value)
                            }
                        >
                            <SelectTrigger id="frequency">
                                <SelectValue placeholder="Select…" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.frequencies.map((frequency) => (
                                    <SelectItem
                                        key={frequency.value}
                                        value={frequency.value}
                                    >
                                        {frequency.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.frequency} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="next_invoice_at">Next invoice</Label>
                        <Input
                            id="next_invoice_at"
                            type="date"
                            value={formData.next_invoice_at}
                            onChange={(event) =>
                                setData('next_invoice_at', event.target.value)
                            }
                        />
                        <InputError message={errors.next_invoice_at} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="end_at">End date</Label>
                        <Input
                            id="end_at"
                            type="date"
                            value={formData.end_at}
                            onChange={(event) =>
                                setData('end_at', event.target.value)
                            }
                        />
                        <InputError message={errors.end_at} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="due_date_days">Due after (days)</Label>
                        <Input
                            id="due_date_days"
                            type="number"
                            min="0"
                            max="365"
                            value={formData.due_date_days}
                            onChange={(event) =>
                                setData('due_date_days', event.target.value)
                            }
                        />
                        <InputError message={errors.due_date_days} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="tax_rate_percent">Tax rate (%)</Label>
                        <Input
                            id="tax_rate_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={formData.tax_rate_percent}
                            onChange={(event) =>
                                setData('tax_rate_percent', event.target.value)
                            }
                        />
                        <InputError message={errors.tax_rate_percent} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="discount_type">Discount type</Label>
                        <Select
                            value={formData.discount_type}
                            onValueChange={(value) =>
                                setData('discount_type', value)
                            }
                        >
                            <SelectTrigger id="discount_type">
                                <SelectValue placeholder="No discount" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.discountTypes.map((discountType) => (
                                    <SelectItem
                                        key={discountType.value}
                                        value={discountType.value}
                                    >
                                        {discountType.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.discount_type} />
                    </div>
                    {formData.discount_type !== '' ? (
                        <div className="grid gap-2">
                            <Label htmlFor="discount_value">
                                Discount value
                            </Label>
                            <Input
                                id="discount_value"
                                type="number"
                                min="0"
                                step="0.01"
                                inputMode="decimal"
                                value={formData.discount_value}
                                onChange={(event) =>
                                    setData(
                                        'discount_value',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.discount_value} />
                        </div>
                    ) : null}
                    <label className="flex items-center gap-2 text-sm lg:col-span-3">
                        <Checkbox
                            checked={formData.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked === true)
                            }
                        />
                        Active schedule
                    </label>
                    <InputError message={errors.is_active} />
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex-row items-center justify-between space-y-0">
                    <CardTitle>Line items</CardTitle>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={addLine}
                    >
                        <PlusCircle />
                        Add line
                    </Button>
                </CardHeader>
                <CardContent className="space-y-4">
                    {formData.items.length === 0 ? (
                        <p className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                            Add at least one line item to this schedule.
                        </p>
                    ) : (
                        formData.items.map((item, index) => {
                            const service = options.services.find(
                                (candidate) =>
                                    String(candidate.id) === item.service_id,
                            );

                            const lineSubtotal =
                                toCents(item.unit_price) *
                                Number(item.quantity || 0);

                            return (
                                <div
                                    key={index}
                                    className={cn(
                                        'rounded-lg border p-4',
                                        index > 0 && 'border-dashed',
                                    )}
                                >
                                    <div className="grid gap-3 lg:grid-cols-12">
                                        <div className="grid gap-2 lg:col-span-3">
                                            <Label
                                                htmlFor={`items.${index}.service_id`}
                                            >
                                                <Link2 className="mr-1 inline size-3.5 align-[-2px]" />
                                                Service
                                            </Label>
                                            <Select
                                                value={item.service_id}
                                                onValueChange={(value) =>
                                                    selectService(index, value)
                                                }
                                            >
                                                <SelectTrigger
                                                    id={`items.${index}.service_id`}
                                                >
                                                    <SelectValue placeholder="Optional" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {options.services.map(
                                                        (candidate) => (
                                                            <SelectItem
                                                                key={
                                                                    candidate.id
                                                                }
                                                                value={String(
                                                                    candidate.id,
                                                                )}
                                                            >
                                                                {candidate.name}
                                                                {candidate.unit
                                                                    ? ` · per ${candidate.unit}`
                                                                    : ''}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2 lg:col-span-3">
                                            <Label
                                                htmlFor={`items.${index}.description`}
                                            >
                                                Description
                                            </Label>
                                            <Input
                                                id={`items.${index}.description`}
                                                value={item.description}
                                                onChange={(event) =>
                                                    updateLine(index, {
                                                        description:
                                                            event.target.value,
                                                    })
                                                }
                                                placeholder="Service or product"
                                            />
                                            <InputError
                                                message={getError(
                                                    errors,
                                                    `items.${index}.description`,
                                                )}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`items.${index}.quantity`}
                                            >
                                                Qty
                                            </Label>
                                            <Input
                                                id={`items.${index}.quantity`}
                                                type="number"
                                                min="0.01"
                                                step="any"
                                                inputMode="decimal"
                                                value={item.quantity}
                                                onChange={(event) =>
                                                    updateLine(index, {
                                                        quantity:
                                                            event.target.value,
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={getError(
                                                    errors,
                                                    `items.${index}.quantity`,
                                                )}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`items.${index}.unit_price`}
                                            >
                                                Unit price
                                            </Label>
                                            <Input
                                                id={`items.${index}.unit_price`}
                                                inputMode="decimal"
                                                value={item.unit_price}
                                                onChange={(event) =>
                                                    updateLine(index, {
                                                        unit_price:
                                                            event.target.value,
                                                    })
                                                }
                                                placeholder="0.00"
                                            />
                                            <InputError
                                                message={getError(
                                                    errors,
                                                    `items.${index}.unit_price`,
                                                )}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`items.${index}.tax_rate_percent`}
                                            >
                                                Tax (%)
                                            </Label>
                                            <Input
                                                id={`items.${index}.tax_rate_percent`}
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={item.tax_rate_percent}
                                                onChange={(event) =>
                                                    updateLine(index, {
                                                        tax_rate_percent:
                                                            event.target.value,
                                                    })
                                                }
                                                placeholder={
                                                    formData.tax_rate_percent ===
                                                    ''
                                                        ? '0'
                                                        : `Invoice ${formData.tax_rate_percent}%`
                                                }
                                            />
                                            <InputError
                                                message={getError(
                                                    errors,
                                                    `items.${index}.tax_rate_percent`,
                                                )}
                                            />
                                        </div>
                                        <div className="flex flex-col items-end justify-between gap-2 lg:col-span-1">
                                            <p className="pt-5 text-sm text-muted-foreground">
                                                {formatCents(lineSubtotal)}
                                            </p>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    removeLine(index)
                                                }
                                                disabled={
                                                    formData.items.length === 1
                                                }
                                                title="Remove line"
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                        {service ? (
                                            <p className="-mt-1 text-xs text-muted-foreground lg:col-span-12">
                                                {service.unit
                                                    ? `${service.unit
                                                          .charAt(0)
                                                          .toUpperCase()}${service.unit.slice(1)}`
                                                    : 'Item'}{' '}
                                                selected — {service.name} at{' '}
                                                {formatCents(
                                                    toCents(service.unit_price),
                                                )}
                                                {service.tax_rate_percent !==
                                                null
                                                    ? ` (${service.tax_rate_percent}% tax)`
                                                    : ''}
                                                .
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                            );
                        })
                    )}
                    <InputError message={getError(errors, 'items')} />
                </CardContent>
                <CardFooter className="flex-col gap-4">
                    <Separator />
                    <div className="ml-auto grid w-full max-w-sm gap-1 text-sm">
                        <div className="flex justify-between text-muted-foreground">
                            <span>Subtotal</span>
                            <span>{formatCents(subtotal)}</span>
                        </div>
                        <div className="flex justify-between text-muted-foreground">
                            <span>Tax</span>
                            <span>{formatCents(Math.round(taxTotal))}</span>
                        </div>
                        {discount > 0 ? (
                            <div className="flex justify-between text-muted-foreground">
                                <span>Discount</span>
                                <span>−{formatCents(discount)}</span>
                            </div>
                        ) : null}
                        <div className="flex items-center justify-between font-semibold">
                            <span className="flex items-center gap-1">
                                <Calculator className="size-4" />
                                Total
                            </span>
                            <span>{formatCents(total)}</span>
                        </div>
                    </div>
                </CardFooter>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Notes &amp; terms</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 lg:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notes</Label>
                        <textarea
                            id="notes"
                            value={formData.notes}
                            onChange={(event) =>
                                setData('notes', event.target.value)
                            }
                            placeholder="Visible on generated invoices…"
                            className="field-sizing-content min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive"
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="terms">Terms</Label>
                        <textarea
                            id="terms"
                            value={formData.terms}
                            onChange={(event) =>
                                setData('terms', event.target.value)
                            }
                            placeholder="Payment terms…"
                            className="field-sizing-content min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive"
                        />
                        <InputError message={errors.terms} />
                    </div>
                </CardContent>
                <CardFooter className="justify-end gap-3">
                    <Button variant="ghost" type="button" asChild>
                        <Link href={RecurringInvoiceController.index.url()}>
                            Cancel
                        </Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving…' : submitLabel}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}
