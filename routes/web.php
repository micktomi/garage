<?php

use App\Http\Controllers\WorkshopController;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->prefix('workshop')->name('workshop.')->group(function () {
    Route::get('/', [WorkshopController::class, 'dashboard'])->name('dashboard');
    Route::get('/work-orders', [WorkshopController::class, 'workOrdersIndex'])->name('work-orders.index');
    Route::get('/work-orders/create', [WorkshopController::class, 'workOrdersCreate'])->name('work-orders.create');
    Route::post('/work-orders', [WorkshopController::class, 'workOrdersStore'])->name('work-orders.store');
    Route::get('/work-orders/{workOrder}', [WorkshopController::class, 'workOrdersShow'])->name('work-orders.show');
    Route::patch('/work-orders/{workOrder}/status', [WorkshopController::class, 'workOrdersUpdateStatus'])->name('work-orders.status');

    Route::get('/customers', [WorkshopController::class, 'customersIndex'])->name('customers.index');
    Route::get('/customers/create', [WorkshopController::class, 'customersCreate'])->name('customers.create');
    Route::post('/customers', [WorkshopController::class, 'customersStore'])->name('customers.store');

    Route::get('/vehicles/create', [WorkshopController::class, 'vehiclesCreate'])->name('vehicles.create');
    Route::post('/vehicles', [WorkshopController::class, 'vehiclesStore'])->name('vehicles.store');

    Route::get('/kteo', [WorkshopController::class, 'kteoIndex'])->name('kteo');

    Route::get('/search', [WorkshopController::class, 'search'])->name('search');

    Route::get('/appointments', [WorkshopController::class, 'appointmentsIndex'])->name('appointments.index');
    Route::get('/appointments/create', [WorkshopController::class, 'appointmentsCreate'])->name('appointments.create');
    Route::post('/appointments', [WorkshopController::class, 'appointmentsStore'])->name('appointments.store');
});

Route::get('/work-orders/{workOrder}/print', function (WorkOrder $workOrder) {
    $workOrder->load('customer', 'vehicle', 'workOrderParts.part');
    return view('work-orders.print', compact('workOrder'));
})->name('work-orders.print')->middleware('auth');
