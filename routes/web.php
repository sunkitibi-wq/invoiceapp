<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => redirect()->route($request->user() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    /**
     * Each resource is registered once per ability so the middleware can name
     * the exact permission the action requires, rather than gating a whole
     * resource on one coarse check.
     */
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('can:invoices.view,payments.view')
        ->name('dashboard');

    Route::resource('customers', CustomerController::class)->only(['index'])->middleware('can:customers.view');
    Route::resource('customers', CustomerController::class)->only(['create', 'store'])->middleware('can:customers.create');
    Route::resource('customers', CustomerController::class)->only(['edit', 'update'])->middleware('can:customers.update');
    Route::resource('customers', CustomerController::class)->only(['destroy'])->middleware('can:customers.delete');

    Route::resource('services', ServiceController::class)->only(['index'])->middleware('can:services.view');
    Route::resource('services', ServiceController::class)->only(['create', 'store'])->middleware('can:services.create');
    Route::resource('services', ServiceController::class)->only(['edit', 'update'])->middleware('can:services.update');
    Route::resource('services', ServiceController::class)->only(['destroy'])->middleware('can:services.delete');

    Route::resource('invoices', InvoiceController::class)->only(['index'])->middleware('can:invoices.view');
    Route::resource('invoices', InvoiceController::class)->only(['create', 'store'])->middleware('can:invoices.create');
    Route::resource('invoices', InvoiceController::class)->only(['show'])->middleware('can:invoices.view');
    Route::resource('invoices', InvoiceController::class)->only(['edit', 'update'])->middleware('can:invoices.update');
    Route::resource('invoices', InvoiceController::class)->only(['destroy'])->middleware('can:invoices.delete');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->middleware('can:invoices.view')->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->middleware('can:invoices.send')->name('invoices.send');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->middleware('can:invoices.void')->name('invoices.void');

    Route::resource('payments', PaymentController::class)->only(['index'])->middleware('can:payments.view');
    Route::get('payments/invoices', [PaymentController::class, 'invoices'])
        ->middleware('can:payments.record')
        ->name('payments.invoices');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])
        ->middleware('can:payments.record')
        ->name('invoices.payments.store');
    Route::delete('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('can:payments.delete')
        ->name('invoices.payments.destroy');

    Route::resource('recurring-invoices', RecurringInvoiceController::class)->only(['index'])->middleware('can:recurring_invoices.view');
    Route::resource('recurring-invoices', RecurringInvoiceController::class)->only(['create', 'store'])->middleware('can:recurring_invoices.create');
    Route::resource('recurring-invoices', RecurringInvoiceController::class)->only(['edit', 'update'])->middleware('can:recurring_invoices.update');
    Route::resource('recurring-invoices', RecurringInvoiceController::class)->only(['destroy'])->middleware('can:recurring_invoices.delete');
    Route::post('recurring-invoices/{recurring_invoice}/toggle', [RecurringInvoiceController::class, 'toggle'])
        ->middleware('can:recurring_invoices.update')
        ->name('recurring-invoices.toggle');

    Route::resource('users', UserController::class)->only(['index'])->middleware('can:users.view');
    Route::resource('users', UserController::class)->only(['create', 'store'])->middleware('can:users.create');
    Route::resource('users', UserController::class)->only(['edit', 'update'])->middleware('can:users.update');
    Route::resource('users', UserController::class)->only(['destroy'])->middleware('can:users.delete');

    Route::resource('roles', RoleController::class)->only(['index'])->middleware('can:roles.view');
    Route::resource('roles', RoleController::class)->only(['create', 'store'])->middleware('can:roles.create');
    Route::resource('roles', RoleController::class)->only(['edit', 'update'])->middleware('can:roles.update');
    Route::resource('roles', RoleController::class)->only(['destroy'])->middleware('can:roles.delete');

    Route::resource('permissions', PermissionController::class)->only(['index'])->middleware('can:permissions.view');
    Route::resource('permissions', PermissionController::class)->only(['create', 'store'])->middleware('can:permissions.create');
    Route::resource('permissions', PermissionController::class)->only(['edit', 'update'])->middleware('can:permissions.update');
    Route::resource('permissions', PermissionController::class)->only(['destroy'])->middleware('can:permissions.delete');
});

require __DIR__.'/settings.php';
