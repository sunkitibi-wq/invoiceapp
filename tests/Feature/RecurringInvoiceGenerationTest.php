<?php

namespace Tests\Feature;

use App\Enums\RecurrenceFrequency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringInvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_invoice_from_a_due_schedule(): void
    {
        $recurring = $this->schedule(
            nextInvoiceAt: now()->subDay(),
            frequency: RecurrenceFrequency::Monthly,
            dueDateDays: 14,
        );

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $invoice = Invoice::sole();

        $this->assertSame($recurring->customer_id, $invoice->customer_id);
        $this->assertSame($recurring->id, $invoice->recurring_invoice_id);
        $this->assertSame(now()->subDay()->toDateString(), $invoice->issue_date->toDateString());
        $this->assertSame(now()->subDay()->addDays(14)->toDateString(), $invoice->due_date->toDateString());
        $this->assertSame(1, $invoice->items()->count());
        $this->assertSame('Consulting', $invoice->items()->sole()->description);
    }

    public function test_it_advances_to_the_next_run_date(): void
    {
        $recurring = $this->schedule(
            nextInvoiceAt: now()->subDay(),
            frequency: RecurrenceFrequency::Monthly,
            dueDateDays: 14,
        );

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertTrue(
            $recurring->refresh()->next_invoice_at->isAfter(now()),
            'The next run date should move into the future.',
        );
        $this->assertSame(
            $recurring->next_invoice_at->addMonth()->toDateString(),
            RecurringInvoice::sole()->next_invoice_at->addMonth()->toDateString(),
        );
    }

    public function test_it_skips_schedules_that_are_not_due_yet(): void
    {
        $this->schedule(nextInvoiceAt: now()->addWeek(), frequency: RecurrenceFrequency::Weekly);

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertSame(0, Invoice::count());
    }

    public function test_it_skips_inactive_schedules(): void
    {
        $this->schedule(
            nextInvoiceAt: now()->subDay(),
            frequency: RecurrenceFrequency::Weekly,
            isActive: false,
        );

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertSame(0, Invoice::count());
    }

    public function test_it_stops_at_the_end_date(): void
    {
        $this->schedule(
            nextInvoiceAt: now()->subDay(),
            frequency: RecurrenceFrequency::Monthly,
            endAt: now()->subMonth(),
        );

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertSame(0, Invoice::count());
    }

    public function test_it_never_generates_more_than_one_invoice_per_run(): void
    {
        $this->schedule(
            nextInvoiceAt: now()->subMonths(6),
            frequency: RecurrenceFrequency::Monthly,
        );

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertSame(1, Invoice::count());
    }

    public function test_it_copies_the_discount_and_tax_from_the_schedule(): void
    {
        $this->schedule(
            nextInvoiceAt: now()->subDay(),
            frequency: RecurrenceFrequency::Monthly,
            taxRate: 0.2,
            discountType: 'percent',
            discountValue: 25,
        );

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $invoice = Invoice::sole();

        $this->assertSame(0.2, $invoice->tax_rate);
        $this->assertSame(25.0, (float) $invoice->discount_value);
        $this->assertTrue($invoice->discount_amount->isPositive());
    }

    public function test_it_creates_a_schedule_from_the_admin_form(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('recurring-invoices.store'), [
                'customer_id' => $customer->id,
                'frequency' => RecurrenceFrequency::Weekly->value,
                'next_invoice_at' => now()->addWeek()->toDateString(),
                'due_date_days' => 7,
                'tax_rate_percent' => 10,
                'items' => [[
                    'service_id' => null,
                    'description' => 'Weekly retainer',
                    'quantity' => 1,
                    'unit_price' => '500.00',
                    'tax_rate_percent' => null,
                ]],
            ])
            ->assertRedirect(route('recurring-invoices.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, RecurringInvoice::count());
        $this->assertSame(1, RecurringInvoice::sole()->items()->count());
    }

    public function test_it_requires_at_least_one_line_item(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('recurring-invoices.store'), [
                'customer_id' => Customer::factory()->create()->id,
                'frequency' => RecurrenceFrequency::Weekly->value,
                'next_invoice_at' => now()->addWeek()->toDateString(),
                'due_date_days' => 7,
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, RecurringInvoice::count());
    }

    public function test_it_toggles_a_schedule(): void
    {
        $recurring = $this->schedule(
            nextInvoiceAt: now()->addWeek(),
            frequency: RecurrenceFrequency::Weekly,
            isActive: false,
        );

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('recurring-invoices.index'))
            ->post(route('recurring-invoices.toggle', $recurring))
            ->assertRedirect();

        $this->assertTrue($recurring->refresh()->is_active);
    }

    /**
     * Create a recurring invoice schedule.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function schedule(
        string $nextInvoiceAt,
        RecurrenceFrequency $frequency,
        int $dueDateDays = 7,
        ?string $endAt = null,
        float $taxRate = 0.0,
        ?string $discountType = null,
        ?float $discountValue = null,
        bool $isActive = true,
        array $overrides = [],
    ): RecurringInvoice {
        $recurring = RecurringInvoice::create([
            'customer_id' => Customer::factory()->create()->id,
            'frequency' => $frequency,
            'next_invoice_at' => $nextInvoiceAt,
            'end_at' => $endAt,
            'due_date_days' => $dueDateDays,
            'tax_rate' => $taxRate,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'is_active' => $isActive,
            ...$overrides,
        ]);

        $recurring->items()->create([
            'service_id' => null,
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => '50.00',
            'tax_rate_percent' => null,
            'position' => 0,
        ]);

        return $recurring;
    }
}
