<?php

namespace App\Actions\Invoicing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Bring an invoice's collected amount and status back in line with its
 * payments.
 *
 * Both recording and deleting a payment must end with the same derived state,
 * so the arithmetic and the status transition live here rather than being
 * repeated at each call site.
 */
class RecalculateInvoicePayment
{
    /**
     * Recalculate the invoice from its current payments.
     */
    public function execute(Invoice $invoice): void
    {
        $amountPaid = Money::of((int) $invoice->payments()->sum('amount'));

        $invoice->update(['amount_paid' => $amountPaid]);

        $invoice->refresh();

        $this->syncStatus($invoice);
    }

    /**
     * Move the invoice to the status its balance and due date call for.
     */
    private function syncStatus(Invoice $invoice): void
    {
        if (in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Void], true)) {
            return;
        }

        $status = $invoice->resolveStatusAfterPayment();

        $invoice->update([
            'status' => $status,
            'paid_at' => $status === InvoiceStatus::Paid
                ? $this->settledAt($invoice)
                : null,
        ]);
    }

    /**
     * Get the date the invoice was actually settled on.
     *
     * This is the date of the most recent payment rather than the current
     * time, so a back dated payment does not stamp the invoice with today's
     * date.
     */
    private function settledAt(Invoice $invoice): Carbon
    {
        $latest = $invoice->payments()->first()?->paid_at;

        return Carbon::parse($latest ?? $invoice->paid_at ?? now());
    }
}
