<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/work-orders/{workOrder}/print', function (App\Models\WorkOrder $workOrder) {
    return view('work-orders.print', compact('workOrder'));
})->name('work-orders.print');
