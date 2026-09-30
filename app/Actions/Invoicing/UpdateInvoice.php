<?php

namespace App\Actions\Invoicing;

use App\Concerns\BuildsInvoiceLines;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Update an invoice from validated input.
 */
class UpdateInvoice
{
    use BuildsInvoiceLines;

    /**
     * Update the invoice and its line items.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data): Invoice {
            $invoice->fill($this->invoiceAttributes($data));

            $invoice->save();

            $this->rebuildInvoiceLines(
                invoice: $invoice,
                items: $data['items'] ?? [],
                discountType: $invoice->discount_type,
                discountValue: (float) ($data['discount_value'] ?? 0),
            );

            // Runs last so that settling against the total picks up any
            // change the line items just made to it.
            $this->applyStatus($invoice, $data['status'] ?? null);

            $invoice->save();

            return $invoice->load(['customer', 'items', 'payments']);
        });
    }

    /**
     * Apply a manually chosen status, keeping the money columns consistent.
     *
     * The status shown on an invoice is normally derived from its payments.
     * Choosing it by hand has to move the figures with it, otherwise the
     * invoice would claim to be paid while still showing a balance, or read
     * as pending while reporting money that was never received.
     *
     * Once a payment has actually been recorded the payments table is the
     * source of truth, so the amounts are left alone and only the status
     * moves. Overwriting a real payment total here would quietly disagree
     * with the payment history the invoice shows.
     */
    private function applyStatus(Invoice $invoice, mixed $status): void
    {
        if ($status === null || $status === '') {
            return;
        }

        $invoice->status = InvoiceStatus::from((string) $status);
        $invoice->sent_at = $this->sentAt($invoice);

        if ($invoice->payments()->exists()) {
            return;
        }

        $invoice->paid_at = $invoice->status === InvoiceStatus::Paid
            ? ($invoice->paid_at ?? Carbon::now())
            : null;
        $invoice->amount_paid = $invoice->status === InvoiceStatus::Paid
            ? $invoice->total
            : Money::zero();
    }

    /**
     * Keep the moment an invoice left draft in step with its status.
     */
    private function sentAt(Invoice $invoice): ?Carbon
    {
        return match ($invoice->status) {
            InvoiceStatus::Draft, InvoiceStatus::Void => null,
            default => $invoice->sent_at ?? Carbon::now(),
        };
    }
}
