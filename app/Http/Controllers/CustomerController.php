<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithTables;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use InteractsWithTables;

    /**
     * Display a listing of the customers.
     */
    public function index(): Response
    {
        $customers = Customer::query()
            ->search(request('search'))
            ->withCount([
                'invoices as invoices_count',
                'invoices as outstanding_invoices_count' => fn ($query) => $query->outstanding(),
            ])
            ->tap(fn ($query) => $this->applySort($query, ['name', 'created_at'], 'name'))
            ->paginate($this->perPage())
            ->withQueryString();

        return Inertia::render('customers/index', [
            'customers' => $customers,
            'filters' => [
                'search' => request('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): Response
    {
        return Inertia::render('customers/create', [
            'customer' => null,
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Customer created.'),
        ]);

        return to_route('customers.index');
    }

    /**
     * Show the form for editing the customer.
     */
    public function edit(Customer $customer): Response
    {
        return Inertia::render('customers/edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the customer.
     */
    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Customer updated.'),
        ]);

        return to_route('customers.index');
    }

    /**
     * Remove the customer, archiving it when invoices still reference it.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $archived = $customer->invoices()->exists();

        $archived
            ? $customer->update(['is_active' => false])
            : $customer->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $archived
                ? __('Customer archived because invoices still reference them.')
                : __('Customer deleted.'),
        ]);

        return to_route('customers.index');
    }
}
