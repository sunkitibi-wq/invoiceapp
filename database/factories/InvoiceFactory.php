<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Support\InvoiceTotals;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Invoice>
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = Carbon::instance(fake()->dateTimeBetween('-3 months', 'now'));

        return [
            'number' => 'INV-'.fake()->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'status' => InvoiceStatus::Draft,
            'issue_date' => $issueDate,
            'due_date' => $issueDate->copy()->addDays(14),
            'currency' => (string) config('invoicing.currency', 'USD'),
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_total' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'notes' => fake()->optional()->sentence(),
            'terms' => config('invoicing.default_terms'),
        ];
    }

    /**
     * Give every invoice a set of line items and matching totals.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Invoice $invoice): void {
            $service = Service::factory()->create();

            $lines = [
                [
                    'quantity' => fake()->numberBetween(1, 8),
                    'unit_price' => $service->unit_price,
                    'tax_rate' => null,
                ],
            ];

            if (fake()->boolean(30)) {
                $lines[] = [
                    'quantity' => 1,
                    'unit_price' => Money::of(fake()->numberBetween(2_500, 50_000)),
                    'tax_rate' => null,
                ];
            }

            $taxRate = fake()->randomElement([0.0, 0.05, 0.1, 0.2]);
            $totals = InvoiceTotals::calculate($lines, defaultTaxRate: $taxRate);

            foreach ($totals->lines as $position => $line) {
                $invoice->items()->create([
                    'service_id' => $service->id,
                    'description' => $service->name,
                    'quantity' => $lines[$position]['quantity'],
                    'unit_price' => $lines[$position]['unit_price'],
                    'tax_rate' => null,
                    'subtotal' => $line->subtotal,
                    'tax_amount' => $line->tax,
                    'total' => $line->total,
                    'position' => $position,
                ]);
            }

            $invoice->update([
                'tax_rate' => $taxRate,
                'subtotal' => $totals->subtotal,
                'tax_total' => $totals->tax,
                'total' => $totals->total,
            ]);
        });
    }

    /**
     * Indicate that the invoice has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the invoice has been sent and is not yet due.
     */
    public function awaitingPayment(): static
    {
        return $this->sent()->state(fn (): array => [
            'issue_date' => now()->subDays(5),
            'due_date' => now()->addDays(9),
        ]);
    }

    /**
     * Indicate that the invoice is past its due date.
     */
    public function overdue(): static
    {
        return $this->sent()->state(fn (): array => [
            'status' => InvoiceStatus::Overdue,
            'issue_date' => now()->subDays(45),
            'due_date' => now()->subDays(15),
        ]);
    }

    /**
     * Indicate that the invoice has been paid in full.
     */
    public function paid(): static
    {
        return $this->sent()->withPayments();
    }

    /**
     * Attach a payment that clears the invoice balance.
     */
    public function withPayments(?float $fraction = null): static
    {
        return $this->afterCreating(function (Invoice $invoice) use ($fraction): void {
            $amount = $fraction === null
                ? $invoice->total
                : $invoice->total->multipliedBy($fraction);

            $invoice->payments()->create([
                'amount' => $amount,
                'method' => PaymentMethod::BankTransfer,
                'paid_at' => now()->subDays(fake()->numberBetween(0, 10)),
                'reference' => strtoupper(fake()->bothify('BACS-####')),
            ]);

            $invoice->update(['amount_paid' => (int) $invoice->payments()->toBase()->sum('amount')]);
            $invoice->refresh();

            $invoice->update([
                'status' => $invoice->isPaid() ? InvoiceStatus::Paid : $invoice->status,
                'paid_at' => $invoice->isPaid() ? now() : null,
            ]);
        });
    }

    /**
     * Indicate that the invoice has a single line item for the given service.
     */
    public function forService(Service|int $service, float $quantity = 1.0): static
    {
        $service = $service instanceof Service ? $service : Service::findOrFail($service);

        return $this->afterCreating(function (Invoice $invoice) use ($service, $quantity): void {
            $invoice->items()->create([
                'service_id' => $service->id,
                'description' => $service->name,
                'quantity' => $quantity,
                'unit_price' => $service->unit_price,
                'tax_rate' => null,
                'subtotal' => $service->unit_price->multipliedBy($quantity),
                'tax_amount' => Money::zero(),
                'total' => $service->unit_price->multipliedBy($quantity),
                'position' => 0,
            ]);

            $invoice->update([
                'subtotal' => $service->unit_price->multipliedBy($quantity),
                'total' => $service->unit_price->multipliedBy($quantity),
            ]);
        });
    }
}
