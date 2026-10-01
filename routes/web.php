<?php

use App\Http\Controllers\VehicleRegistrationScanController;
use App\Http\Controllers\WorkshopAssistantController;
use App\Http\Controllers\WorkshopController;
use App\Http\Middleware\HandleWorkshopInertiaRequests;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth', HandleWorkshopInertiaRequests::class])->prefix('workshop')->name('workshop.')->group(function () {
    Route::get('/', [WorkshopController::class, 'dashboard'])->name('dashboard');
    Route::get('/work-orders', [WorkshopController::class, 'workOrdersIndex'])->name('work-orders.index');
    Route::get('/work-orders/create', [WorkshopController::class, 'workOrdersCreate'])->name('work-orders.create');
    Route::post('/work-orders', [WorkshopController::class, 'workOrdersStore'])->name('work-orders.store');
    Route::get('/work-orders/{workOrder}/edit', [WorkshopController::class, 'workOrdersEdit'])->name('work-orders.edit');
    Route::put('/work-orders/{workOrder}', [WorkshopController::class, 'workOrdersUpdate'])->name('work-orders.update');
    Route::get('/work-orders/{workOrder}', [WorkshopController::class, 'workOrdersShow'])->name('work-orders.show');
    Route::patch('/work-orders/{workOrder}/status', [WorkshopController::class, 'workOrdersUpdateStatus'])->name('work-orders.status');

    Route::get('/customers', [WorkshopController::class, 'customersIndex'])->name('customers.index');
    Route::get('/customers/create', [WorkshopController::class, 'customersCreate'])->name('customers.create');
    Route::post('/customers', [WorkshopController::class, 'customersStore'])->name('customers.store');

    Route::get('/customers/{customer}', [WorkshopController::class, 'customersShow'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [WorkshopController::class, 'customersEdit'])->name('customers.edit');
    Route::put('/customers/{customer}', [WorkshopController::class, 'customersUpdate'])->name('customers.update');

    Route::get('/vehicles', [WorkshopController::class, 'vehiclesIndex'])->name('vehicles.index');
    Route::get('/vehicles/create', [WorkshopController::class, 'vehiclesCreate'])->name('vehicles.create');
    Route::post('/vehicles', [WorkshopController::class, 'vehiclesStore'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}/edit', [WorkshopController::class, 'vehiclesEdit'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [WorkshopController::class, 'vehiclesUpdate'])->name('vehicles.update');

    Route::get('/registration-scan', [VehicleRegistrationScanController::class, 'show'])->name('registration-scan.show');
    Route::post('/registration-scan/extract', [VehicleRegistrationScanController::class, 'extract'])
        ->middleware('throttle:6,1')
        ->name('registration-scan.extract');
    Route::post('/registration-scan', [VehicleRegistrationScanController::class, 'store'])->name('registration-scan.store');

    Route::get('/assistant', [WorkshopAssistantController::class, 'show'])->name('assistant');
    Route::post('/assistant', [WorkshopAssistantController::class, 'update'])->name('assistant.update');
    Route::get('/parts', [WorkshopController::class, 'partsIndex'])->name('parts.index');
    Route::get('/kteo', [WorkshopController::class, 'kteoIndex'])->name('kteo');

    Route::get('/search', [WorkshopController::class, 'search'])->name('search');

    Route::get('/appointments', [WorkshopController::class, 'appointmentsIndex'])->name('appointments.index');
    Route::get('/appointments/create', [WorkshopController::class, 'appointmentsCreate'])->name('appointments.create');
    Route::get('/appointments/{appointment}/edit', [WorkshopController::class, 'appointmentsEdit'])->name('appointments.edit');
    Route::put('/appointments/{appointment}', [WorkshopController::class, 'appointmentsUpdate'])->name('appointments.update');
    Route::post('/appointments', [WorkshopController::class, 'appointmentsStore'])->name('appointments.store');
});

Route::get('/work-orders/{workOrder}/print', function (WorkOrder $workOrder) {
    $workOrder->load('customer', 'vehicle', 'workOrderParts.part');

    return view('work-orders.print', compact('workOrder'));
})->name('work-orders.print')->middleware('auth');
