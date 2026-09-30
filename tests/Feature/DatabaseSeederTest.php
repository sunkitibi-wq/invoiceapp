<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_leaves_no_settled_invoice_in_an_unpaid_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        $settled = Invoice::query()
            ->where('total', '>', 0)
            ->whereColumn('amount_paid', 'total');

        $this->assertSame(0, (clone $settled)
            ->where('status', '!=', InvoiceStatus::Paid->value)
            ->count());
    }

    public function test_it_marks_fully_paid_invoices_as_paid(): void
    {
        $this->seed(DatabaseSeeder::class);

        $paid = Invoice::query()
            ->where('total', '>', 0)
            ->whereColumn('amount_paid', 'total')
            ->get();

        $this->assertNotEmpty($paid);

        $paid->each(function (Invoice $invoice): void {
            $this->assertSame(InvoiceStatus::Paid, $invoice->status);
            $this->assertNotNull($invoice->paid_at);
        });
    }

    public function test_it_leaves_partially_paid_invoices_outstanding(): void
    {
        $this->seed(DatabaseSeeder::class);

        $partial = Invoice::query()
            ->whereColumn('amount_paid', '>', 0)
            ->whereColumn('amount_paid', '<', 'total')
            ->get();

        $this->assertNotEmpty($partial);

        $partial->each(function (Invoice $invoice): void {
            $this->assertNotSame(InvoiceStatus::Paid, $invoice->status);
            $this->assertNull($invoice->paid_at);
        });
    }
}
