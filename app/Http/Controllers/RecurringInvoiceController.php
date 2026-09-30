<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithTables;
use App\Enums\DiscountType;
use App\Enums\RecurrenceFrequency;
use App\Http\Requests\RecurringInvoiceRequest;
use App\Models\Customer;
use App\Models\RecurringInvoice;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class RecurringInvoiceController extends Controller
{
    use InteractsWithTables;

    /**
     * Display a listing of the recurring schedules.
     */
    public function index(): Response
    {
        $recurringInvoices = RecurringInvoice::query()
            ->with('customer')
            ->tap(fn ($query) => $this->applySort($query, [
                'frequency',
                'next_invoice_at',
                'created_at',
            ], 'next_invoice_at'))
            ->paginate($this->perPage())
            ->withQueryString();

        return Inertia::render('recurring-invoices/index', [
            'recurringInvoices' => $recurringInvoices,
            'frequencies' => collect(RecurrenceFrequency::cases())
                ->map(fn (RecurrenceFrequency $frequency): array => [
                    'value' => $frequency->value,
                    'label' => $frequency->label(),
                ])
                ->all(),
        ]);
    }

    /**
     * Show the form for creating a new recurring schedule.
     */
    public function create(): Response
    {
        return Inertia::render('recurring-invoices/create', [
            'recurringInvoice' => null,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created recurring schedule.
     */
    public function store(RecurringInvoiceRequest $request): RedirectResponse
    {
        $this->persist($request);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Recurring invoice created.'),
        ]);

        return to_route('recurring-invoices.index');
    }

    /**
     * Show the form for editing the recurring schedule.
     */
    public function edit(RecurringInvoice $recurringInvoice): Response
    {
        return Inertia::render('recurring-invoices/edit', [
            'recurringInvoice' => $recurringInvoice->load('items'),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the recurring schedule.
     */
    public function update(RecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice): RedirectResponse
    {
        $this->persist($request, $recurringInvoice);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Recurring invoice updated.'),
        ]);

        return to_route('recurring-invoices.index');
    }

    /**
     * Pause or resume the recurring schedule.
     */
    public function toggle(RecurringInvoice $recurringInvoice): RedirectResponse
    {
        $recurringInvoice->update(['is_active' => ! $recurringInvoice->is_active]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $recurringInvoice->is_active
                ? __('Recurring invoice resumed.')
                : __('Recurring invoice paused.'),
        ]);

        return back();
    }

    /**
     * Remove the recurring schedule.
     */
    public function destroy(RecurringInvoice $recurringInvoice): RedirectResponse
    {
        $recurringInvoice->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Recurring invoice deleted.'),
        ]);

        return to_route('recurring-invoices.index');
    }

    /**
     * Create or update the schedule and its line items.
     */
    private function persist(RecurringInvoiceRequest $request, ?RecurringInvoice $recurringInvoice = null): void
    {
        $data = Arr::except($request->validated(), 'items');

        $recurringInvoice ??= new RecurringInvoice;
        $recurringInvoice->fill($data)->save();

        $recurringInvoice->items()->delete();

        foreach ($request->validated('items') as $position => $item) {
            $recurringInvoice->items()->create([
                ...$item,
                'position' => $position,
            ]);
        }
    }

    /**
     * Get the options shared by the recurring invoice forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'company'])
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
            'frequencies' => collect(RecurrenceFrequency::cases())
                ->map(fn (RecurrenceFrequency $frequency): array => [
                    'value' => $frequency->value,
                    'label' => $frequency->label(),
                ])
                ->all(),
            'discountTypes' => collect(DiscountType::cases())
                ->map(fn (DiscountType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->all(),
            'defaults' => [
                'tax_rate_percent' => (float) config('invoicing.default_tax_rate') * 100,
                'due_date_days' => (int) config('invoicing.default_payment_terms_days'),
                'notes' => config('invoicing.default_notes'),
                'terms' => config('invoicing.default_terms'),
            ],
        ];
    }
}
