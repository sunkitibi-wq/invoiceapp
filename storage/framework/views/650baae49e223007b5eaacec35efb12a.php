<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo e($invoice->number); ?></title>
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
                <p style="font-size: 14px; font-weight: bold; margin: 0"><?php echo e($from['name']); ?></p>
                <?php if($from['address_line_1'] ?? null): ?>
                    <p class="muted" style="margin: 0">
                        <?php echo e(collect([$from['address_line_1'], $from['address_line_2']])->filter()->implode(', ')); ?><br>
                        <?php echo e(collect([$from['city'], $from['state'], $from['postal_code']])->filter()->implode(', ')); ?>

                        <?php if($from['country'] ?? null): ?><br><?php echo e($from['country']); ?><?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if($from['email'] ?? null): ?>
                    <p class="muted" style="margin: 0"><?php echo e($from['email']); ?></p>
                <?php endif; ?>
                <?php if($from['phone'] ?? null): ?>
                    <p class="muted" style="margin: 0"><?php echo e($from['phone']); ?></p>
                <?php endif; ?>
                <?php if($from['tax_number'] ?? null): ?>
                    <p class="muted" style="margin: 0"><?php echo e($from['tax_number']); ?></p>
                <?php endif; ?>
            </td>
            <td class="text-right">
                <p class="heading" style="margin: 0 0 4px">Invoice</p>
                <p class="title" style="margin: 0 0 6px"><?php echo e($invoice->number); ?></p>
                <p class="badge"><?php echo e($invoice->status_label); ?></p>
            </td>
        </tr>
    </table>

    <table style="margin-top: 28px">
        <tr class="rule">
            <td style="width: 55%">
                <p class="heading" style="margin: 0 0 4px">Bill to</p>
                <p style="font-size: 14px; font-weight: bold; margin: 0"><?php echo e($customer->billing_name); ?></p>
                <?php if($customer->name !== $customer->billing_name): ?>
                    <p class="muted" style="margin: 0"><?php echo e($customer->name); ?></p>
                <?php endif; ?>
                <?php if($customer->address): ?>
                    <p class="muted" style="margin: 0"><?php echo nl2br(e($customer->address)); ?></p>
                <?php endif; ?>
                <?php if($customer->email): ?>
                    <p class="muted" style="margin: 0"><?php echo e($customer->email); ?></p>
                <?php endif; ?>
                <?php if($customer->tax_number): ?>
                    <p class="muted" style="margin: 0"><?php echo e($customer->tax_number); ?></p>
                <?php endif; ?>
            </td>
            <td class="text-right" style="width: 45%">
                <table>
                    <tr>
                        <td class="muted">Issue date</td>
                        <td class="text-right"><?php echo e($invoice->issue_date->format('j M Y')); ?></td>
                    </tr>
                    <tr>
                        <td class="muted">Due date</td>
                        <td class="text-right"><?php echo e($invoice->due_date->format('j M Y')); ?></td>
                    </tr>
                    <tr>
                        <td class="muted">Amount due</td>
                        <td class="text-right"><strong><?php echo e($invoice->balance->format()); ?></strong></td>
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
            <?php $__empty_1 = true; $__currentLoopData = $invoice->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="muted"><?php echo e($loop->iteration); ?></td>
                    <td><?php echo e($item->description); ?></td>
                    <td class="text-right"><?php echo e(rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.')); ?></td>
                    <td class="text-right"><?php echo e($item->unit_price->format()); ?></td>
                    <td class="text-right"><?php echo e(rtrim(rtrim(number_format((float) ($item->tax_rate ?? $invoice->tax_rate) * 100, 2, '.', ''), '0'), '.')); ?>%</td>
                    <td class="text-right"><?php echo e($item->total->format()); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="muted">No line items.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table style="margin-top: 16px">
        <tr>
            <td style="width: 55%"></td>
            <td style="width: 45%">
                <table class="totals">
                    <tr>
                        <td class="muted">Subtotal</td>
                        <td class="text-right"><?php echo e($invoice->subtotal->format()); ?></td>
                    </tr>
                    <?php if($invoice->discount_amount->isPositive()): ?>
                        <tr>
                            <td class="muted">
                                Discount
                                <?php if($invoice->discount_type): ?>
                                    (<?php echo e($invoice->discount_type->label()); ?>)
                                <?php endif; ?>
                            </td>
                            <td class="text-right">-<?php echo e($invoice->discount_amount->format()); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if($invoice->tax_total->isPositive()): ?>
                        <tr>
                            <td class="muted">Tax</td>
                            <td class="text-right"><?php echo e($invoice->tax_total->format()); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="grand">
                        <td>Total</td>
                        <td class="text-right"><?php echo e($invoice->total->format()); ?></td>
                    </tr>
                    <?php if($invoice->amount_paid->isPositive()): ?>
                        <tr>
                            <td class="muted">Paid</td>
                            <td class="text-right">-<?php echo e($invoice->amount_paid->format()); ?></td>
                        </tr>
                        <tr>
                            <td class="muted">Balance due</td>
                            <td class="text-right"><strong><?php echo e($invoice->balance->format()); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
    </table>

    <?php if($invoice->payments->isNotEmpty()): ?>
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
                            <?php $__currentLoopData = $invoice->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($payment->paid_at->format('j M Y')); ?></td>
                                    <td><?php echo e($payment->method_label); ?></td>
                                    <td><?php echo e($payment->reference); ?></td>
                                    <td class="text-right"><?php echo e($payment->amount->format()); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <?php if($invoice->notes || $invoice->terms): ?>
        <table style="margin-top: 28px">
            <tr>
                <?php if($invoice->notes): ?>
                    <td style="width: 50%">
                        <p class="heading" style="margin: 0 0 4px">Notes</p>
                        <p style="margin: 0"><?php echo nl2br(e($invoice->notes)); ?></p>
                    </td>
                <?php endif; ?>
                <?php if($invoice->terms): ?>
                    <td style="width: 50%">
                        <p class="heading" style="margin: 0 0 4px">Terms</p>
                        <p style="margin: 0"><?php echo nl2br(e($invoice->terms)); ?></p>
                    </td>
                <?php endif; ?>
            </tr>
        </table>
    <?php endif; ?>
</body>
</html>
<?php /**PATH C:\Users\User\Herd\toma\resources\views/invoices/pdf.blade.php ENDPATH**/ ?>