<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RecurringInvoice;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the invoicing dashboard.
     */
    public function index(): Response
    {
        $outstanding = Invoice::query()
            ->outstanding()
            ->toBase()
            ->selectRaw('coalesce(sum(total), 0) - coalesce(sum(amount_paid), 0) as outstanding_total')
            ->value('outstanding_total');

        $receivedThisMonth = Payment::query()
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return Inertia::render('dashboard', [
            'stats' => [
                [
                    'label' => __('Received this month'),
                    'value' => Money::of((int) $receivedThisMonth)->format(),
                    'href' => route('payments.index', ['period' => 'month']),
                ],
                [
                    'label' => __('Outstanding'),
                    'value' => Money::of((int) $outstanding)->format(),
                    'href' => route('invoices.index', ['status' => 'outstanding']),
                ],
                [
                    'label' => __('Overdue'),
                    'value' => (string) Invoice::query()->overdue()->count(),
                    'href' => route('invoices.index', ['status' => InvoiceStatus::Overdue->value]),
                ],
                [
                    'label' => __('Drafts'),
                    'value' => (string) Invoice::query()->withStatus(InvoiceStatus::Draft)->count(),
                    'href' => route('invoices.index', ['status' => InvoiceStatus::Draft->value]),
                ],
            ],
            'recentInvoices' => Invoice::query()
                ->with('customer')
                ->latest('issue_date')
                ->latest('id')
                ->limit(8)
                ->get(),
            'upcomingRecurring' => RecurringInvoice::query()
                ->with('customer')
                ->active()
                ->orderBy('next_invoice_at')
                ->limit(5)
                ->get(),
            'customerCount' => Customer::query()->count(),
        ]);
    }
}
