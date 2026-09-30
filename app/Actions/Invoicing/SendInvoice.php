<?php

namespace App\Actions\Invoicing;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Email an invoice to the customer and move it out of draft.
 */
class SendInvoice
{
    /**
     * Send the invoice.
     *
     * @throws ValidationException
     */
    public function execute(Invoice $invoice): void
    {
        $invoice->loadMissing('customer');

        if ($invoice->status !== InvoiceStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('Only draft invoices can be sent.'),
            ]);
        }

        $email = $invoice->customer->email;

        if ($email === null || $email === '') {
            throw ValidationException::withMessages([
                'customer' => __('Add an email address to the customer before sending an invoice.'),
            ]);
        }

        Mail::to($email)->send(new InvoiceMail($invoice));

        $invoice->update([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);
    }
}
