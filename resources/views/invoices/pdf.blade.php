<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { margin: 32px 36px; }

        body {
            color: #18181b;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
        }

        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 6px 8px; text-align: left; vertical-align: top; }
        .text-right { text-align: right; }
        .muted { color: #71717a; }
        .heading { font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; color: #71717a; }
        .title { font-size: 26px; letter-spacing: 0.04em; margin: 0; }
        .rule { border-bottom: 1px solid #e4e4e7; }
        .items th { border-bottom: 1px solid #18181b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; }
        .items td { border-bottom: 1px solid #e4e4e7; }
        .totals td { padding: 4px 8px; }
        .totals .grand td { border-top: 1px solid #18181b; font-size: 14px; font-weight: bold; }
        .badge { border: 1px solid #18181b; border-radius: 999px; display: inline-block; font-size: 10px; letter-spacing: 0.06em; padding: 2px 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="width: 55%">
                <p class="heading" style="margin: 0 0 4px">From</p>
                <p style="font-size: 14px; font-weight: bold; margin: 0">{{ $from['name'] }}</p>
                @if ($from['address_line_1'] ?? null)
                    <p class="muted" style="margin: 0">
                        {{ collect([$from['address_line_1'], $from['address_line_2']])->filter()->implode(', ') }}<br>
                        {{ collect([$from['city'], $from['state'], $from['postal_code']])->filter()->implode(', ') }}
                        @if ($from['country'] ?? null)<br>{{ $from['country'] }}@endif
                    </p>
                @endif
                @if ($from['email'] ?? null)
                    <p class="muted" style="margin: 0">{{ $from['email'] }}</p>
                @endif
                @if ($from['phone'] ?? null)
                    <p class="muted" style="margin: 0">{{ $from['phone'] }}</p>
                @endif
                @if ($from['tax_number'] ?? null)
                    <p class="muted" style="margin: 0">{{ $from['tax_number'] }}</p>
                @endif
            </td>
            <td class="text-right">
                <p class="heading" style="margin: 0 0 4px">Invoice</p>
                <p class="title" style="margin: 0 0 6px">{{ $invoice->number }}</p>
                <p class="badge">{{ $invoice->status_label }}</p>
            </td>
        </tr>
    </table>

    <table style="margin-top: 28px">
        <tr class="rule">
            <td style="width: 55%">
                <p class="heading" style="margin: 0 0 4px">Bill to</p>
                <p style="font-size: 14px; font-weight: bold; margin: 0">{{ $customer->billing_name }}</p>
                @if ($customer->name !== $customer->billing_name)
                    <p class="muted" style="margin: 0">{{ $customer->name }}</p>
                @endif
                @if ($customer->address)
                    <p class="muted" style="margin: 0">{!! nl2br(e($customer->address)) !!}</p>
                @endif
                @if ($customer->email)
                    <p class="muted" style="margin: 0">{{ $customer->email }}</p>
                @endif
                @if ($customer->tax_number)
                    <p class="muted" style="margin: 0">{{ $customer->tax_number }}</p>
                @endif
            </td>
            <td class="text-right" style="width: 45%">
                <table>
                    <tr>
                        <td class="muted">Issue date</td>
                        <td class="text-right">{{ $invoice->issue_date->format('j M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Due date</td>
                        <td class="text-right">{{ $invoice->due_date->format('j M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Amount due</td>
                        <td class="text-right"><strong>{{ $invoice->balance->format() }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 28px">
        <thead>
            <tr>
                <th style="width: 24px">#</th>
                <th>Description</th>
                <th class="text-right" style="width: 60px">Qty</th>
                <th class="text-right" style="width: 90px">Unit price</th>
                <th class="text-right" style="width: 70px">Tax</th>
                <th class="text-right" style="width: 100px">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->items as $item)
                <tr>
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.') }}</td>
                    <td class="text-right">{{ $item->unit_price->format() }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) ($item->tax_rate ?? $invoice->tax_rate) * 100, 2, '.', ''), '0'), '.') }}%</td>
                    <td class="text-right">{{ $item->total->format() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No line items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table style="margin-top: 16px">
        <tr>
            <td style="width: 55%"></td>
            <td style="width: 45%">
                <table class="totals">
                    <tr>
                        <td class="muted">Subtotal</td>
                        <td class="text-right">{{ $invoice->subtotal->format() }}</td>
                    </tr>
                    @if ($invoice->discount_amount->isPositive())
                        <tr>
                            <td class="muted">
                                Discount
                                @if ($invoice->discount_type)
                                    ({{ $invoice->discount_type->label() }})
                                @endif
                            </td>
                            <td class="text-right">-{{ $invoice->discount_amount->format() }}</td>
                        </tr>
                    @endif
                    @if ($invoice->tax_total->isPositive())
                        <tr>
                            <td class="muted">Tax</td>
                            <td class="text-right">{{ $invoice->tax_total->format() }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td>Total</td>
                        <td class="text-right">{{ $invoice->total->format() }}</td>
                    </tr>
                    @if ($invoice->amount_paid->isPositive())
                        <tr>
                            <td class="muted">Paid</td>
                            <td class="text-right">-{{ $invoice->amount_paid->format() }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Balance due</td>
                            <td class="text-right"><strong>{{ $invoice->balance->format() }}</strong></td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if ($invoice->payments->isNotEmpty())
        <table style="margin-top: 28px">
            <tr>
                <td>
                    <p class="heading" style="margin: 0 0 4px">Payments received</p>
                </td>
            </tr>
            <tr>
                <td>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at->format('j M Y') }}</td>
                                    <td>{{ $payment->method_label }}</td>
                                    <td>{{ $payment->reference }}</td>
                                    <td class="text-right">{{ $payment->amount->format() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    @if ($invoice->notes || $invoice->terms)
        <table style="margin-top: 28px">
            <tr>
                @if ($invoice->notes)
                    <td style="width: 50%">
                        <p class="heading" style="margin: 0 0 4px">Notes</p>
                        <p style="margin: 0">{!! nl2br(e($invoice->notes)) !!}</p>
                    </td>
                @endif
                @if ($invoice->terms)
                    <td style="width: 50%">
                        <p class="heading" style="margin: 0 0 4px">Terms</p>
                        <p style="margin: 0">{!! nl2br(e($invoice->terms)) !!}</p>
                    </td>
                @endif
            </tr>
        </table>
    @endif
</body>
</html>
