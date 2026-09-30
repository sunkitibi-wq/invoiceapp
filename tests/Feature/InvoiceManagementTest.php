<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_invoice_with_calculated_totals(): void
    {
        $this->actingAs($this->admin())
            ->post(route('invoices.store'), [
                'customer_id' => $customer = Customer::factory()->create()->id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'tax_rate_percent' => 10,
                'discount_type' => 'percent',
                'discount_value' => 10,
                'notes' => 'Thanks!',
                'items' => [
                    [
                        'service_id' => null,
                        'description' => 'Consulting',
                        'quantity' => 2,
                        'unit_price' => '150.00',
                        'tax_rate_percent' => null,
                    ],
                    [
                        'service_id' => null,
                        'description' => 'Travel',
                        'quantity' => 1,
                        'unit_price' => '50.00',
                        'tax_rate_percent' => 0,
                    ],
                ],
            ])
            ->assertRedirect();

        $invoice = Invoice::sole();

        $this->assertSame($customer, $invoice->customer_id);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame(35_000, $invoice->subtotal->cents);
        $this->assertSame(3_500, $invoice->discount_amount->cents);
        $this->assertSame(2_700, $invoice->tax_total->cents);
        $this->assertSame(34_200, $invoice->total->cents);
        $this->assertSame(34_200, $invoice->balance->cents);
        $this->assertSame(2, $invoice->items()->count());
        $this->assertTrue(str_starts_with($invoice->number, 'INV-'.now()->year.'-'));
    }

    public function test_it_numbers_each_invoice_in_sequence(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();

        foreach (range(1, 3) as $ignored) {
            $this->actingAs($admin)
                ->post(route('invoices.store'), $this->payload($customer->id))
                ->assertRedirect();
        }

        $this->assertSame(
            ['INV-'.now()->year.'-0001', 'INV-'.now()->year.'-0002', 'INV-'.now()->year.'-0003'],
            Invoice::pluck('number')->all(),
        );
    }

    public function test_it_copies_details_from_a_service_onto_the_line(): void
    {
        $service = Service::factory()->create([
            'name' => 'Support retainer',
            'description' => 'Monthly support',
            'unit_price' => '250.00',
            'tax_rate' => 0.2,
        ]);

        $this->actingAs($this->admin())
            ->post(route('invoices.store'), [
                'customer_id' => Customer::factory()->create()->id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'tax_rate_percent' => 10,
                'items' => [[
                    'service_id' => $service->id,
                    'description' => $service->name,
                    'quantity' => 1,
                    'unit_price' => $service->unit_price->toInput(),
                    'tax_rate_percent' => $service->tax_rate_percent,
                ]],
            ])
            ->assertRedirect();

        $item = InvoiceItem::sole();

        $this->assertSame($service->id, $item->service_id);
        $this->assertSame(25_000, $item->unit_price->cents);
        $this->assertSame(0.2, $item->tax_rate);
        $this->assertSame(5_000, $item->tax_amount->cents);
    }

    public function test_it_recalculates_totals_when_lines_change(): void
    {
        $invoice = Invoice::factory()->create(['tax_rate' => 0.1]);
        $item = $invoice->items()->first();

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $invoice->customer_id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'tax_rate_percent' => 20,
                'items' => [[
                    'service_id' => null,
                    'description' => $item->description,
                    'quantity' => 2,
                    'unit_price' => $item->unit_price->toInput(),
                    'tax_rate_percent' => null,
                ]],
            ])
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame(1, $invoice->items()->count());
        $this->assertSame($item->unit_price->cents * 2, $invoice->subtotal->cents);
        $this->assertSame((int) round($invoice->subtotal->cents * 0.2), $invoice->tax_total->cents);
    }

    public function test_it_rejects_an_invoice_without_line_items(): void
    {
        $this->actingAs($this->admin())
            ->post(route('invoices.store'), [
                'customer_id' => Customer::factory()->create()->id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Invoice::count());
    }

    public function test_it_rejects_a_due_date_before_the_issue_date(): void
    {
        $this->actingAs($this->admin())
            ->post(route('invoices.store'), [
                'customer_id' => Customer::factory()->create()->id,
                'issue_date' => '2026-02-01',
                'due_date' => '2026-01-15',
                'items' => [$this->itemPayload()],
            ])
            ->assertSessionHasErrors('due_date');
    }

    public function test_a_draft_invoice_can_be_edited_and_deleted(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $invoice->customer_id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'items' => [$this->itemPayload()],
            ])
            ->assertRedirect();

        $this->actingAs($this->admin())
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect();

        $this->assertSame(0, Invoice::count());
    }

    public function test_a_sent_invoice_can_be_edited_but_not_deleted(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $invoice->customer_id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'items' => [$this->itemPayload()],
            ])
            ->assertRedirect();

        $this->actingAs($this->admin())
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();

        $this->assertSame(1, Invoice::count());
    }

    public function test_a_paid_invoice_can_neither_be_edited_nor_deleted(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $invoice->customer_id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'items' => [$this->itemPayload()],
            ])
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();

        $this->assertSame(1, Invoice::count());
    }

    public function test_a_void_invoice_can_neither_be_edited_nor_deleted(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Void]);

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $invoice->customer_id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'items' => [$this->itemPayload()],
            ])
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();
    }

    public function test_it_lists_and_filters_invoices(): void
    {
        $customer = Customer::factory()->create();

        Invoice::factory()->create(['status' => InvoiceStatus::Overdue, 'due_date' => now()->subDay()]);
        Invoice::factory()->create(['status' => InvoiceStatus::Paid, 'due_date' => now()->addMonth()]);

        $this->actingAs($this->admin())
            ->get(route('invoices.index', ['status' => 'overdue', 'customer' => $customer->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoices.data', fn ($rows) => $rows->count() === 1)
                ->where('filters.status', 'overdue'),
            );
    }

    public function test_it_searches_invoices_by_number_and_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Northwind Traders']);
        Invoice::factory()->create(['customer_id' => $customer->id]);
        Invoice::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('invoices.index', ['search' => 'Northwind']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoices.data', fn ($rows) => $rows->count() === 1),
            );
    }

    public function test_sending_an_invoice_records_when_it_was_sent(): void
    {
        Mail::fake();

        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

        $this->actingAs($this->admin())
            ->from(route('invoices.show', $invoice))
            ->post(route('invoices.send', $invoice))
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        $this->assertNotNull($invoice->sent_at);
    }

    public function test_a_sent_invoice_cannot_be_sent_again(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

        $this->actingAs($this->admin())
            ->post(route('invoices.send', $invoice))
            ->assertSessionHasErrors('status');
    }

    public function test_a_paid_invoice_cannot_be_voided(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->actingAs($this->admin())
            ->post(route('invoices.void', $invoice))
            ->assertForbidden();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    public function test_voiding_an_invoice_keeps_it_for_the_record(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

        $this->actingAs($this->admin())
            ->post(route('invoices.void', $invoice))
            ->assertRedirect();

        $this->assertSame(InvoiceStatus::Void, $invoice->refresh()->status);
    }

    public function test_it_shows_a_single_invoice(): void
    {
        $invoice = Invoice::factory()->withPayments()->create();

        $this->actingAs($this->admin())
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoice.number', $invoice->number)
                ->where('invoice.payments', fn ($payments) => $payments->count() === 1),
            );
    }

    public function test_a_draft_status_can_be_changed_to_another_status(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                ...$this->payload($invoice->customer_id),
                'status' => InvoiceStatus::Overdue->value,
            ])
            ->assertRedirect();

        $this->assertSame(InvoiceStatus::Overdue, $invoice->refresh()->status);
    }

    public function test_marking_an_invoice_paid_settles_the_balance(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                ...$this->payload($invoice->customer_id),
                'status' => InvoiceStatus::Paid->value,
            ])
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame($invoice->total->cents, $invoice->amount_paid->cents);
        $this->assertTrue($invoice->isPaid());
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_marking_an_invoice_paid_uses_the_recalculated_total(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $invoice->customer_id,
                'issue_date' => '2026-01-01',
                'due_date' => '2026-01-15',
                'status' => InvoiceStatus::Paid->value,
                'items' => [
                    [
                        'service_id' => null,
                        'description' => 'Consulting',
                        'quantity' => 3,
                        'unit_price' => '100.00',
                        'tax_rate_percent' => null,
                    ],
                ],
            ])
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame(30000, $invoice->total->cents);
        $this->assertSame(30000, $invoice->amount_paid->cents);
    }

    public function test_marking_an_invoice_paid_locks_it_against_further_edits(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                ...$this->payload($invoice->customer_id),
                'status' => InvoiceStatus::Paid->value,
            ])
            ->assertRedirect();

        // Settled invoices are terminal, so the status cannot be walked back.
        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                ...$this->payload($invoice->customer_id),
                'status' => InvoiceStatus::Sent->value,
            ])
            ->assertForbidden();

        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);
    }

    public function test_an_invoice_with_recorded_payments_keeps_its_amounts(): void
    {
        $invoice = Invoice::factory()
            ->withPayments(0.5)
            ->create(['status' => InvoiceStatus::PartiallyPaid]);

        $amountPaid = $invoice->amount_paid->cents;

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                ...$this->payload($invoice->customer_id),
                'status' => InvoiceStatus::Overdue->value,
            ])
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Overdue, $invoice->status);
        $this->assertSame($amountPaid, $invoice->amount_paid->cents);
    }

    public function test_it_rejects_a_status_outside_the_catalogue(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('invoices.update', $invoice), [
                ...$this->payload($invoice->customer_id),
                'status' => 'refunded',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(InvoiceStatus::Draft, $invoice->refresh()->status);
    }

    public function test_the_sent_status_is_labelled_pending(): void
    {
        $this->assertSame('Pending', InvoiceStatus::Sent->label());
    }

    /**
     * A minimal valid invoice payload.
     */
    private function payload(int $customerId): array
    {
        return [
            'customer_id' => $customerId,
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'items' => [$this->itemPayload()],
        ];
    }

    /**
     * A minimal valid line item.
     */
    private function itemPayload(): array
    {
        return [
            'service_id' => null,
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_price' => '100.00',
            'tax_rate_percent' => null,
        ];
    }

    /**
     * Create an admin user.
     */
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
