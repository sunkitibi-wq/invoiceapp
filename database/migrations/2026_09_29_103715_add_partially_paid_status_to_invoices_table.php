<?php

use App\Enums\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $partial = InvoiceStatus::PartiallyPaid->value;
        $sent = InvoiceStatus::Sent->value;
        $overdue = InvoiceStatus::Overdue->value;

        // An invoice that is both part paid and past its due date is reported
        // as overdue, so lateness is never hidden by a partial payment.
        DB::table('invoices')
            ->where('status', $sent)
            ->where('amount_paid', '>', 0)
            ->whereColumn('amount_paid', '<', 'total')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => $overdue]);

        DB::table('invoices')
            ->where('status', $sent)
            ->where('amount_paid', '>', 0)
            ->whereColumn('amount_paid', '<', 'total')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->update(['status' => $partial]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('invoices')
            ->where('status', InvoiceStatus::PartiallyPaid->value)
            ->update(['status' => InvoiceStatus::Sent->value]);
    }
};
