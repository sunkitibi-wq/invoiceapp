<?php

namespace Tests\Feature;

use App\Actions\Invoicing\RecordPayment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_a_partial_payment(): void
    {
        $invoice = Invoice::factory()->awaitingPayment()->create();
        $total = $invoice->total->cents;
        $half = $invoice->balance->multipliedBy(0.5);

        $this->actingAs($this->admin())
            ->from(route('invoices.show', $invoice))
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => $half->toInput(),
                'method' => PaymentMethod::BankTransfer->value,
                'paid_at' => '2026-03-01',
                'reference' => 'BACS-0001',
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();

        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame($half->cents, $invoice->amount_paid->cents);
        $this->assertSame($total - $invoice->amount_paid->cents, $invoice->balance->cents);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_it_marks_the_invoice_paid_when_the_balance_is_cleared(): void
    {
        $invoice = Invoice::factory()->sent()->create();
        $total = $invoice->total->cents;

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => '10.00',
                'method' => PaymentMethod::Cash->value,
                'paid_at' => '2026-03-01',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => $invoice->refresh()->balance->toInput(),
                'method' => PaymentMethod::Cash->value,
                'paid_at' => '2026-03-02',
            ])
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame($total, $invoice->amount_paid->cents);
        $this->assertTrue($invoice->isPaid());
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_it_clears_the_overdue_status_when_paid(): void
    {
        $invoice = Invoice::factory()->overdue()->create();

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => $invoice->total->toInput(),
                'method' => PaymentMethod::BankTransfer->value,
                'paid_at' => '2026-03-01',
            ])
            ->assertRedirect();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    public function test_it_rejects_a_payment_larger_than_the_balance(): void
    {
        $invoice = Invoice::factory()->sent()->create();

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => $invoice->balance->plus(100)->toInput(),
                'method' => PaymentMethod::Cash->value,
                'paid_at' => '2026-03-01',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Payment::count());
    }

    public function test_it_rejects_a_payment_with_no_value(): void
    {
        $invoice = Invoice::factory()->sent()->create();

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => '0',
                'method' => PaymentMethod::Cash->value,
                'paid_at' => '2026-03-01',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_it_cannot_pay_a_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => '10.00',
                'method' => PaymentMethod::Cash->value,
                'paid_at' => '2026-03-01',
            ])
            ->assertForbidden();

        $this->assertSame(0, Payment::count());
    }

    public function test_it_cannot_pay_a_void_invoice(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Void]);

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => '10.00',
                'method' => PaymentMethod::Cash->value,
                'paid_at' => '2026-03-01',
            ])
            ->assertForbidden();
    }

    public function test_it_deletes_a_payment_and_reopens_the_invoice(): void
    {
        $invoice = Invoice::factory()->awaitingPayment()->withPayments()->create();
        $payment = $invoice->payments()->sole();

        $this->actingAs($this->admin())
            ->delete(route('invoices.payments.destroy', [$invoice, $payment]))
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();

        $this->assertSame(0, $invoice->payments()->count());
        $this->assertSame(0, $invoice->amount_paid->cents);
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_a_part_paid_invoice_that_is_past_due_is_reported_as_overdue(): void
    {
        $invoice = Invoice::factory()->overdue()->create();

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => $invoice->balance->multipliedBy(0.25)->toInput(),
                'method' => PaymentMethod::Cash->value,
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(InvoiceStatus::Overdue, $invoice->refresh()->status);
    }

    public function test_it_stamps_paid_at_from_the_latest_payment_date(): void
    {
        $invoice = Invoice::factory()->awaitingPayment()->create();

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => '10.00',
                'method' => PaymentMethod::Card->value,
                'paid_at' => '2026-01-10',
            ]);

        $this->actingAs($this->admin())
            ->post(route('invoices.payments.store', $invoice), [
                'amount' => $invoice->refresh()->balance->toInput(),
                'method' => PaymentMethod::Card->value,
                'paid_at' => '2026-01-20',
            ]);

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame('2026-01-20', $invoice->paid_at?->toDateString());
    }

    public function test_deleting_a_payment_from_an_overdue_invoice_keeps_it_overdue(): void
    {
        $invoice = Invoice::factory()->overdue()->withPayments()->create();
        $payment = $invoice->payments()->sole();

        $this->actingAs($this->admin())
            ->delete(route('invoices.payments.destroy', [$invoice, $payment]))
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();

        $this->assertSame(0, $invoice->amount_paid->cents);
        $this->assertSame(InvoiceStatus::Overdue, $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_a_payment_cannot_be_deleted_from_another_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create();
        $other = Invoice::factory()->paid()->create();

        $this->actingAs($this->admin())
            ->delete(route('invoices.payments.destroy', [$other, $invoice->payments()->sole()]))
            ->assertNotFound();

        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_a_repeated_submission_cannot_overspend_a_stale_invoice(): void
    {
        $invoice = Invoice::factory()->sent()->create();

        /**
         * A retried or double submitted request can arrive holding a model that
         * was loaded before the first payment landed, so the amount has to be
         * checked against the balance read under a lock rather than the copy
         * the caller was handed.
         */
        $stale = Invoice::query()->findOrFail($invoice->getKey());
        $fullBalance = $stale->balance->toInput();

        $payment = [
            'amount' => $fullBalance,
            'method' => PaymentMethod::Cash->value,
            'paid_at' => '2026-03-01',
        ];

        app(RecordPayment::class)->execute($stale, $payment);

        try {
            app(RecordPayment::class)->execute($stale, $payment);

            $this->fail('The repeated submission should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    /**
     * Create an admin user.
     */
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
