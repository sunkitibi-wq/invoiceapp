<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkInvoicesOverdueTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_unpaid_invoices_that_are_past_due(): void
    {
        $sent = Invoice::factory()->sent()->create(['due_date' => now()->subDay()]);
        $overdue = Invoice::factory()->overdue()->create();
        $draft = Invoice::factory()->create(['due_date' => now()->subWeek()]);
        $future = Invoice::factory()->sent()->create(['due_date' => now()->addDay()]);
        $paid = Invoice::factory()->paid()->create(['due_date' => now()->subDay()]);

        $this->artisan('invoices:mark-overdue')->assertSuccessful();

        $this->assertSame(InvoiceStatus::Overdue, $sent->refresh()->status);
        $this->assertSame(InvoiceStatus::Overdue, $overdue->refresh()->status);
        $this->assertSame(InvoiceStatus::Draft, $draft->refresh()->status);
        $this->assertSame(InvoiceStatus::Sent, $future->refresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $paid->refresh()->status);
    }

    public function test_it_does_nothing_when_every_invoice_is_current(): void
    {
        Invoice::factory()->sent()->create(['due_date' => now()->addDay()]);

        $this->artisan('invoices:mark-overdue')
            ->expectsOutputToContain('0')
            ->assertSuccessful();
    }

    public function test_an_invoice_due_today_is_not_overdue(): void
    {
        $invoice = Invoice::factory()->sent()->create(['due_date' => now()]);

        $this->artisan('invoices:mark-overdue')->assertSuccessful();

        $this->assertSame(InvoiceStatus::Sent, $invoice->refresh()->status);
    }

    public function test_overdue_invoices_are_reported_by_the_scope(): void
    {
        Invoice::factory()->overdue()->count(2)->create();
        Invoice::factory()->sent()->create(['due_date' => now()->addWeek()]);

        $this->assertSame(2, Invoice::query()->overdue()->count());
    }
}
