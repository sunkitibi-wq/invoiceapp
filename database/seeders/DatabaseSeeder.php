<?php

namespace Database\Seeders;

use App\Actions\Invoicing\CreateInvoice;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\RecurrenceFrequency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\Service;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::factory()->admin()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
        ]);

        $customers = Customer::factory()->count(8)->create();

        $services = $this->seedServices();

        $this->seedInvoices($customers->take(6), $services);
        $this->seedRecurringInvoices($customers->first(), $services);

        $this->command->info("Seeded an admin user: {$admin->email} / password");
    }

    /**
     * Create the services offered to customers.
     *
     * @return Collection<int, Service>
     */
    private function seedServices(): Collection
    {
        return Service::factory()->createMany([
            [
                'name' => 'Consulting',
                'unit' => 'hour',
                'description' => 'Advisory and hands-on consulting work.',
                'unit_price' => 2_500_000,
            ],
            [
                'name' => 'Web development',
                'unit' => 'hour',
                'description' => 'Design and implementation of web applications.',
                'unit_price' => 2_000_000,
                'tax_rate' => 0.1,
            ],
            [
                'name' => 'Support retainer',
                'unit' => 'month',
                'description' => 'On-call support and maintenance for the month.',
                'unit_price' => 35_000_000,
            ],
        ])->collect();
    }

    /**
     * Create a spread of invoices in different states.
     *
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Service>  $services
     */
    private function seedInvoices(Collection $customers, Collection $services): void
    {
        $createInvoice = app(CreateInvoice::class);

        foreach ($customers as $index => $customer) {
            $service = $services[$index % $services->count()];
            $issueDate = now()->subDays($index * 9);

            $invoice = $createInvoice->execute([
                'customer_id' => $customer->id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $issueDate->copy()->addDays(14)->toDateString(),
                'tax_rate' => 0.1,
                'discount_type' => $index === 0 ? 'percent' : null,
                'discount_value' => $index === 0 ? 5 : null,
                'notes' => 'Thank you for your business.',
                'terms' => config('invoicing.default_terms'),
                'items' => [
                    [
                        'service_id' => $service->id,
                        'description' => $service->name,
                        'quantity' => 6 + $index,
                        'unit_price' => $service->unit_price->toInput(),
                        'tax_rate' => null,
                    ],
                    [
                        'service_id' => null,
                        'description' => 'Additional expenses',
                        'quantity' => 1,
                        'unit_price' => '15000.00',
                        'tax_rate' => null,
                    ],
                ],
            ]);

            $this->seedInvoiceState($invoice, $issueDate, $index);
        }
    }

    /**
     * Move the invoice into the state that matches its position in the list.
     */
    private function seedInvoiceState(Invoice $invoice, CarbonImmutable $issueDate, int $index): void
    {
        if ($index === 0) {
            return;
        }

        $invoice->update([
            'status' => InvoiceStatus::Sent,
            'sent_at' => $issueDate,
        ]);

        if ($index !== 1) {
            $this->recordPayment($invoice, $invoice->total, $issueDate->copy()->addDays(3));

            return;
        }

        $this->recordPayment($invoice, $invoice->balance->multipliedBy(0.5), $issueDate->copy()->addDays(3));

        $invoice->update(['status' => InvoiceStatus::Overdue]);
    }

    /**
     * Record a payment against the invoice.
     */
    private function recordPayment(Invoice $invoice, Money $amount, CarbonImmutable $paidAt): void
    {
        $invoice->payments()->create([
            'amount' => $amount,
            'method' => PaymentMethod::BankTransfer,
            'paid_at' => $paidAt,
            'reference' => 'BACS-'.str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT),
        ]);

        $invoice->update([
            'amount_paid' => (int) $invoice->payments()->sum('amount'),
        ]);

        $invoice->refresh();

        if ($invoice->isPaid()) {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => $paidAt,
            ]);
        }
    }

    /**
     * Create a recurring schedule for the first customer.
     *
     * @param  Collection<int, Service>  $services
     */
    private function seedRecurringInvoices(?Customer $customer, Collection $services): void
    {
        if ($customer === null) {
            return;
        }

        $service = $services->last();

        $recurring = RecurringInvoice::create([
            'customer_id' => $customer->id,
            'frequency' => RecurrenceFrequency::Monthly,
            'next_invoice_at' => now()->addDays(3),
            'due_date_days' => 14,
            'tax_rate' => 0.1,
            'notes' => 'Automatically generated each month.',
            'terms' => config('invoicing.default_terms'),
            'is_active' => true,
        ]);

        $recurring->items()->create([
            'service_id' => $service->id,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => $service->unit_price,
            'tax_rate' => null,
            'position' => 0,
        ]);
    }
}
