<?php

namespace App\Http\Controllers;

use App\Actions\Invoicing\CreateInvoice;
use App\Actions\Invoicing\SendInvoice;
use App\Actions\Invoicing\UpdateInvoice;
use App\Concerns\InteractsWithTables;
use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\InvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Support\InvoicePdf;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use InteractsWithTables;

    /**
     * Display a listing of the invoices.
     */
    public function index(): Response
    {
        $status = request('status');

        $invoices = Invoice::query()
            ->with('customer')
            ->search(request('search'))
            ->when(
                $status === 'outstanding',
                fn (Builder $query): Builder => $query->outstanding(),
                fn (Builder $query): Builder => $query->withStatus($status),
            )
            ->tap(fn ($query) => $this->applySort($query, [
                'number',
                'status',
                'issue_date',
                'due_date',
                'total',
            ], 'issue_date', 'desc'))
            ->paginate($this->perPage())
            ->withQueryString();

        $outstanding = Invoice::query()
            ->outstanding()
            ->toBase()
            ->selectRaw('coalesce(sum(total), 0) - coalesce(sum(amount_paid), 0) as outstanding_total')
            ->value('outstanding_total');

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => request('search'),
                'status' => $status,
            ],
            'statuses' => [
                ['value' => 'outstanding', 'label' => __('Outstanding')],
                ...collect(InvoiceStatus::cases())
                    ->map(fn (InvoiceStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->all(),
            ],
            'summary' => [
                'outstanding' => Money::of((int) $outstanding)->format(),
                'overdue' => Invoice::query()->overdue()->count(),
                'draft' => Invoice::query()->withStatus(InvoiceStatus::Draft)->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(): Response
    {
        return Inertia::render('invoices/create', [
            'invoice' => null,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(InvoiceRequest $request, CreateInvoice $createInvoice): RedirectResponse
    {
        $invoice = $createInvoice->execute($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice :number created.', ['number' => $invoice->number]),
        ]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Display the invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['customer', 'items', 'payments']);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'paymentMethods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ])
                ->all(),
        ]);
    }

    /**
     * Show the form for editing the invoice.
     */
    public function edit(Invoice $invoice): Response
    {
        return Inertia::render('invoices/edit', [
            'invoice' => $invoice->load('items'),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the invoice.
     */
    public function update(InvoiceRequest $request, Invoice $invoice, UpdateInvoice $updateInvoice): RedirectResponse
    {
        $updateInvoice->execute($invoice, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice updated.'),
        ]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Remove the invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        // Editing an issued invoice is fine, but deleting one erases a record
        // that may already have been sent or paid. Void it instead.
        abort_unless($invoice->status === InvoiceStatus::Draft, 403);

        $invoice->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice deleted.'),
        ]);

        return to_route('invoices.index');
    }

    /**
     * Email the invoice to the customer.
     */
    public function send(Invoice $invoice, SendInvoice $sendInvoice): RedirectResponse
    {
        $sendInvoice->execute($invoice);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice sent to :email.', [
                'email' => $invoice->customer->email,
            ]),
        ]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Void the invoice.
     */
    public function void(Invoice $invoice): RedirectResponse
    {
        abort_if(
            in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Void], true),
            403,
        );

        $invoice->update(['status' => InvoiceStatus::Void]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invoice voided.'),
        ]);

        return to_route('invoices.show', $invoice);
    }

    /**
     * Download the invoice as a PDF.
     */
    public function pdf(Invoice $invoice, InvoicePdf $pdf): HttpResponse
    {
        return $pdf->download($invoice);
    }

    /**
     * Get the options shared by the invoice forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'company', 'email'])
                ->all(),
            'services' => Service::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn (Service $service): array => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'unit' => $service->unit,
                    'description' => $service->description,
                    'unit_price' => $service->unit_price->toInput(),
                    'tax_rate_percent' => $service->tax_rate === null
                        ? null
                        : (float) ($service->tax_rate * 100),
                ])
                ->all(),
            'discountTypes' => collect(DiscountType::cases())
                ->map(fn (DiscountType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->all(),
            'statuses' => collect(InvoiceStatus::cases())
                ->map(fn (InvoiceStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->all(),
            'defaults' => [
                'tax_rate_percent' => (float) config('invoicing.default_tax_rate') * 100,
                'payment_terms_days' => (int) config('invoicing.default_payment_terms_days'),
                'notes' => config('invoicing.default_notes'),
                'terms' => config('invoicing.default_terms'),
            ],
        ];
    }
}
