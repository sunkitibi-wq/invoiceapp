<?php

namespace App\Console\Commands;

use App\Actions\Invoicing\GenerateRecurringInvoices;
use Illuminate\Console\Command;

class GenerateRecurringInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create draft invoices for recurring schedules that are due';

    /**
     * Execute the console command.
     */
    public function handle(GenerateRecurringInvoices $generateRecurringInvoices): int
    {
        $count = $generateRecurringInvoices->execute();

        $this->info("Generated {$count} invoice(s).");

        return self::SUCCESS;
    }
}
