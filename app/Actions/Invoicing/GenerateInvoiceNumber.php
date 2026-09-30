<?php

namespace App\Actions\Invoicing;

use App\Models\DocumentSequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Reserve the next invoice number, e.g. INV-2026-0007.
 */
class GenerateInvoiceNumber
{
    /**
     * Generate the next invoice number for the given date.
     */
    public function execute(?string $date = null): string
    {
        $period = $this->period($date);

        $number = DB::transaction(fn (): int => DocumentSequence::reserve('invoice', $period));

        return implode('-', array_filter([
            (string) config('invoicing.number_prefix', 'INV'),
            $period === 'all' ? null : $period,
            str_pad((string) $number, (int) config('invoicing.number_padding', 4), '0', STR_PAD_LEFT),
        ]));
    }

    /**
     * Determine the sequence period the given date belongs to.
     */
    private function period(?string $date): string
    {
        if (config('invoicing.number_period', 'year') !== 'year') {
            return 'all';
        }

        return CarbonImmutable::parse($date ?? 'now')->format('Y');
    }
}
