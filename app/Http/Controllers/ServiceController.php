<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithTables;
use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use InteractsWithTables;

    /**
     * Display a listing of the services.
     */
    public function index(): Response
    {
        $services = Service::query()
            ->search(request('search'))
            ->tap(fn ($query) => $this->applySort($query, ['name', 'unit_price', 'created_at'], 'name'))
            ->paginate($this->perPage())
            ->withQueryString();

        return Inertia::render('services/index', [
            'services' => $services,
            'filters' => [
                'search' => request('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): Response
    {
        return Inertia::render('services/create', [
            'service' => null,
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Service created.'),
        ]);

        return to_route('services.index');
    }

    /**
     * Show the form for editing the service.
     */
    public function edit(Service $service): Response
    {
        return Inertia::render('services/edit', [
            'service' => $service,
        ]);
    }

    /**
     * Update the service.
     */
    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Service updated.'),
        ]);

        return to_route('services.index');
    }

    /**
     * Remove the service, archiving it when invoices still reference it.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $archived = $service->invoiceItems()->exists();

        $archived
            ? $service->update(['is_active' => false])
            : $service->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $archived
                ? __('Service archived because invoices still reference it.')
                : __('Service deleted.'),
        ]);

        return to_route('services.index');
    }
}
