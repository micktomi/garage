<?php

use App\Models\WorkOrder;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/work-orders/{workOrder}/print', function (WorkOrder $workOrder) {
    $workOrder->load('customer', 'vehicle', 'workOrderParts.part');
    return view('work-orders.print', compact('workOrder'));
})->name('work-orders.print')->middleware('auth');
