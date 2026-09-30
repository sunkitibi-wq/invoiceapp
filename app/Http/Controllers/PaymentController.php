<?php

namespace App\Http\Controllers;

use App\Actions\Invoicing\RecalculateInvoicePayment;
use App\Actions\Invoicing\RecordPayment;
use App\Concerns\InteractsWithTables;
use App\Enums\PaymentMethod;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    use InteractsWithTables;

    /**
     * Display every payment recorded across all invoices.
     */
    public function index(): Response
    {
        $filters = [
            'search' => request('search'),
            'method' => request('method'),
            'period' => request('period'),
        ];

        $query = Payment::query()
            ->with('invoice.customer')
            ->search($filters['search'])
            ->withMethod($filters['method'])
            ->inPeriod($filters['period']);

        $payments = (clone $query)
            ->tap(fn ($query) => $this->applySort($query, [
                'amount',
                'method',
                'paid_at',
            ], 'paid_at', 'desc'))
            ->paginate($this->perPage())
            ->withQueryString();

        $outstanding = Invoice::query()
            ->outstanding()
            ->toBase()
            ->selectRaw('coalesce(sum(total), 0) - coalesce(sum(amount_paid), 0) as outstanding_total')
            ->value('outstanding_total');

        return Inertia::render('payments/index', [
            'payments' => $payments,
            'filters' => $filters,
            'paymentMethods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ])
                ->all(),
            'periods' => [
                ['value' => 'today', 'label' => __('Today')],
                ['value' => 'week', 'label' => __('Last 7 days')],
                ['value' => 'month', 'label' => __('This month')],
                ['value' => 'quarter', 'label' => __('Last 90 days')],
                ['value' => 'year', 'label' => __('This year')],
            ],
            'summary' => [
                'received' => Money::of((int) (clone $query)->sum('amount'))->format(),
                'count' => (clone $query)->count(),
                'outstanding' => Money::of((int) $outstanding)->format(),
            ],
        ]);
    }

    /**
     * Search the invoices that can still receive a payment.
     */
    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->outstanding()
            ->with('customer')
            ->search($request->string('search')->trim()->value() ?: null)
            ->orderBy('due_date')
            ->limit(25)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'customer' => $invoice->customer->billing_name,
                'due_date' => $invoice->due_date,
                'balance' => $invoice->balance,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
            ]);

        return response()->json(['invoices' => $invoices]);
    }

    /**
     * Record a payment against the invoice.
     */
    public function store(PaymentRequest $request, Invoice $invoice, RecordPayment $recordPayment): RedirectResponse
    {
        $recordPayment->execute($invoice, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment recorded.'),
        ]);

        return redirect()->back(fallback: to_route('invoices.show', $invoice));
    }

    /**
     * Remove the payment and update the invoice balance.
     */
    public function destroy(
        Invoice $invoice,
        Payment $payment,
        RecalculateInvoicePayment $recalculate,
    ): RedirectResponse {
        abort_unless($payment->invoice_id === $invoice->id, 404);

        $payment->delete();

        $recalculate->execute($invoice);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment deleted.'),
        ]);

        return redirect()->back(fallback: route('invoices.show', $invoice));
    }
}
