<?php

namespace App\Actions\Invoicing;

use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turn due recurring schedules into draft invoices.
 */
class GenerateRecurringInvoices
{
    public function __construct(private readonly CreateInvoice $createInvoice) {}

    /**
     * Generate an invoice for every schedule that is due.
     */
    public function execute(): int
    {
        $generated = 0;

        RecurringInvoice::query()
            ->due()
            ->with(['customer', 'items'])
            ->chunkById(50, function ($schedules) use (&$generated): void {
                foreach ($schedules as $schedule) {
                    DB::transaction(function () use ($schedule, &$generated): void {
                        $this->createInvoice->execute($this->attributes($schedule));

                        $schedule->update([
                            'next_invoice_at' => $schedule->nextRunDate(),
                            'last_generated_at' => now(),
                        ]);

                        $generated++;
                    });
                }
            });

        return $generated;
    }

    /**
     * Build the invoice attributes for the schedule's next occurrence.
     *
     * @return array<string, mixed>
     */
    private function attributes(RecurringInvoice $schedule): array
    {
        $issueDate = Carbon::parse($schedule->next_invoice_at);

        return [
            'customer_id' => $schedule->customer_id,
            'recurring_invoice_id' => $schedule->id,
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $issueDate->copy()->addDays($schedule->due_date_days)->toDateString(),
            'tax_rate' => $schedule->tax_rate,
            'discount_type' => $schedule->discount_type?->value,
            'discount_value' => $schedule->discount_type === null ? null : $schedule->discount_value,
            'notes' => $schedule->notes ?? config('invoicing.default_notes'),
            'terms' => $schedule->terms ?? config('invoicing.default_terms'),
            'items' => $schedule->items
                ->map(fn ($item): array => [
                    'service_id' => $item->service_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                ])
                ->all(),
        ];
    }
}
