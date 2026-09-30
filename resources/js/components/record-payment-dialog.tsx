import { useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Banknote, ChevronLeft, Search } from 'lucide-react';
import PaymentController from '@/actions/App/Http/Controllers/PaymentController';
import InputError from '@/components/input-error';
import { InvoiceStatusBadge } from '@/components/invoice-status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/lib/format';
import type { Invoice, InvoiceStatus, Money, Option } from '@/types';

export type SelectableInvoice = {
    id: number;
    number: string;
    customer: string | null;
    due_date: string;
    balance: Money;
    status: InvoiceStatus;
    status_label: string;
};

type Props = {
    paymentMethods: Option[];
    /** When provided the picker is skipped and this invoice is paid directly. */
    invoice?: Invoice;
    trigger?: ReactNode;
    onRecorded?: (invoiceId: number) => void;
};

function toDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function toAmountInput(balance: Money): string {
    return (balance.cents / 100).toFixed(2);
}

export function RecordPaymentDialog({
    paymentMethods,
    invoice,
    trigger,
    onRecorded,
}: Props) {
    const [open, setOpen] = useState(false);
    const [selected, setSelected] = useState<SelectableInvoice | null>(null);
    const [search, setSearch] = useState('');
    const [results, setResults] = useState<SelectableInvoice[]>([]);
    const [searching, setSearching] = useState(false);

    const target = invoice
        ? ({
              id: invoice.id,
              number: invoice.number,
              customer: invoice.customer?.billing_name ?? null,
              due_date: invoice.due_date,
              balance: invoice.balance,
              status: invoice.status,
              status_label: invoice.status_label,
          } satisfies SelectableInvoice)
        : selected;

    const { data, setData, errors, processing, post, reset } = useForm({
        amount: '',
        method: '',
        paid_at: toDateInput(new Date()),
        reference: '',
        note: '',
    });

    useEffect(() => {
        if (invoice || !open || selected) {
            return;
        }

        const controller = new AbortController();
        setSearching(true);

        const timer = window.setTimeout(() => {
            const query = new URLSearchParams();

            if (search.trim()) {
                query.set('search', search.trim());
            }

            const url = query.size
                ? `${PaymentController.invoices.url()}?${query}`
                : PaymentController.invoices.url();

            fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((response) => response.json())
                .then((payload: { invoices: SelectableInvoice[] }) =>
                    setResults(payload.invoices),
                )
                .catch(() => {
                    if (!controller.signal.aborted) {
                        setResults([]);
                    }
                })
                .finally(() => setSearching(false));
        }, 250);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [search, open, selected, invoice]);

    useEffect(() => {
        if (!target) {
            return;
        }

        setData((current) => ({
            ...current,
            amount: current.amount || toAmountInput(target.balance),
        }));
    }, [target, setData]);

    const handleOpenChange = (next: boolean) => {
        setOpen(next);

        if (!next) {
            setSelected(null);
            setSearch('');
            reset();
        }
    };

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (!target) {
            return;
        }

        post(PaymentController.store.url({ invoice: target.id }), {
            onSuccess: () => {
                onRecorded?.(target.id);
                handleOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" variant="outline">
                        <Banknote />
                        Record payment
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={handleSubmit} noValidate>
                    <DialogHeader>
                        <DialogTitle>
                            {target ? 'Record payment' : 'Choose an invoice'}
                        </DialogTitle>
                        <DialogDescription>
                            {target
                                ? `Add a payment against ${target.number}.`
                                : 'Search the invoices that still have a balance due.'}
                        </DialogDescription>
                    </DialogHeader>

                    {!target ? (
                        <div className="grid gap-3 py-4">
                            <div className="relative">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search by invoice number or customer…"
                                    className="pl-9"
                                    autoFocus
                                />
                            </div>

                            <div className="max-h-72 overflow-y-auto rounded-md border">
                                {searching && results.length === 0 ? (
                                    <p className="p-4 text-sm text-muted-foreground">
                                        Searching…
                                    </p>
                                ) : results.length === 0 ? (
                                    <p className="p-4 text-sm text-muted-foreground">
                                        No outstanding invoices found.
                                    </p>
                                ) : (
                                    <ul className="divide-y">
                                        {results.map((option) => (
                                            <li key={option.id}>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setSelected(option)
                                                    }
                                                    className="flex w-full items-center justify-between gap-3 p-3 text-left text-sm transition-colors hover:bg-accent"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="font-medium">
                                                            {option.number}
                                                        </p>
                                                        <p className="truncate text-xs text-muted-foreground">
                                                            {option.customer ??
                                                                'No customer'}
                                                        </p>
                                                    </div>
                                                    <div className="flex shrink-0 flex-col items-end gap-1">
                                                        <span className="font-medium">
                                                            {
                                                                option.balance
                                                                    .formatted
                                                            }
                                                        </span>
                                                        <InvoiceStatusBadge
                                                            status={
                                                                option.status
                                                            }
                                                            label={
                                                                option.status_label
                                                            }
                                                        />
                                                    </div>
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-4 py-4">
                            {!invoice ? (
                                <div className="flex items-center justify-between gap-3 rounded-md border bg-muted/40 p-3 text-sm">
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {target.number}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {target.customer ?? 'No customer'} ·
                                            due {formatDate(target.due_date)}
                                        </p>
                                    </div>
                                    <span className="shrink-0 font-semibold">
                                        {target.balance.formatted} due
                                    </span>
                                </div>
                            ) : null}

                            <div className="grid gap-2">
                                <Label htmlFor="amount">Amount</Label>
                                <Input
                                    id="amount"
                                    inputMode="decimal"
                                    value={data.amount}
                                    onChange={(event) =>
                                        setData('amount', event.target.value)
                                    }
                                />
                                <InputError message={errors.amount} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Method</Label>
                                <Select
                                    value={data.method}
                                    onValueChange={(value) =>
                                        setData('method', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a method…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {paymentMethods.map((method) => (
                                            <SelectItem
                                                key={method.value}
                                                value={method.value}
                                            >
                                                {method.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.method} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="paid_at">Paid on</Label>
                                <Input
                                    id="paid_at"
                                    type="date"
                                    value={data.paid_at}
                                    max={toDateInput(new Date())}
                                    onChange={(event) =>
                                        setData('paid_at', event.target.value)
                                    }
                                />
                                <InputError message={errors.paid_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="reference">Reference</Label>
                                <Input
                                    id="reference"
                                    value={data.reference}
                                    onChange={(event) =>
                                        setData('reference', event.target.value)
                                    }
                                    placeholder="Bank transfer reference"
                                />
                                <InputError message={errors.reference} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="note">Note</Label>
                                <Input
                                    id="note"
                                    value={data.note}
                                    onChange={(event) =>
                                        setData('note', event.target.value)
                                    }
                                    placeholder="Optional note"
                                />
                                <InputError message={errors.note} />
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        {!target ? (
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => handleOpenChange(false)}
                            >
                                Cancel
                            </Button>
                        ) : (
                            <>
                                {!invoice ? (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => {
                                            setSelected(null);
                                            setData('amount', '');
                                        }}
                                    >
                                        <ChevronLeft />
                                        Choose another
                                    </Button>
                                ) : null}
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Recording…'
                                        : 'Record payment'}
                                </Button>
                            </>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
