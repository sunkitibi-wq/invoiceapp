<?php

namespace App\Actions\Invoicing;

use App\Concerns\BuildsInvoiceLines;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Create an invoice from validated input.
 */
class CreateInvoice
{
    use BuildsInvoiceLines;

    public function __construct(private readonly GenerateInvoiceNumber $generateInvoiceNumber) {}

    /**
     * Create a draft invoice with its line items.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $invoice = new Invoice([
                ...$this->invoiceAttributes($data),
                'number' => $this->generateInvoiceNumber->execute($data['issue_date'] ?? null),
                'status' => InvoiceStatus::Draft,
                'currency' => (string) config('invoicing.currency', 'USD'),
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_total' => 0,
                'total' => 0,
                'amount_paid' => 0,
            ]);

            $invoice->save();

            $this->rebuildInvoiceLines(
                invoice: $invoice,
                items: $data['items'] ?? [],
                discountType: $invoice->discount_type,
                discountValue: (float) ($data['discount_value'] ?? 0),
            );

            return $invoice->load(['customer', 'items', 'payments']);
        });
    }
}
