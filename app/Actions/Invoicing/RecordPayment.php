<?php

namespace App\Actions\Invoicing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Record a payment against an invoice and keep its balance up to date.
 */
class RecordPayment
{
    /**
     * Record the payment.
     *
     * The invoice row is locked for the duration of the write and the amount is
     * checked against the balance read inside that lock, so two concurrent
     * submissions of the same payment cannot both succeed.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(Invoice $invoice, array $data): Payment
    {
        $this->guardAgainstUnpayableInvoice($invoice);

        $amount = Money::fromInput((string) $data['amount']);

        return DB::transaction(function () use ($invoice, $data, $amount): Payment {
            $locked = Invoice::query()
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardAmountWithinBalance($locked, $amount);

            $payment = $locked->payments()->create([
                ...$data,
                'amount' => $amount,
            ]);

            app(RecalculateInvoicePayment::class)->execute($locked);

            return $payment;
        });
    }

    /**
     * Ensure the amount does not exceed what is still owed.
     *
     * @throws ValidationException
     */
    private function guardAmountWithinBalance(Invoice $invoice, Money $amount): void
    {
        if ($amount->cents <= $invoice->balance->cents) {
            return;
        }

        throw ValidationException::withMessages([
            'amount' => __('The amount is greater than the outstanding balance of :amount.', [
                'amount' => $invoice->balance->format(),
            ]),
        ]);
    }

    /**
     * Ensure payments can be recorded against the invoice.
     *
     * @throws ValidationException
     */
    private function guardAgainstUnpayableInvoice(Invoice $invoice): void
    {
        if (in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Void], true)) {
            throw ValidationException::withMessages([
                'amount' => __('Payments cannot be recorded against a :status invoice.', [
                    'status' => $invoice->status->label(),
                ]),
            ]);
        }
    }
}
