<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'customer_id',
        'plate_number',
        'make',
        'model',
        'year',
        'kteo_expires_at',
        'mileage',
        'vin',
        'notes',
    ];

    protected $casts = [
        'kteo_expires_at' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function latestTrackedWorkOrder(): ?WorkOrder
    {
        return $this->workOrders()
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('current_mileage')
                    ->orWhereNotNull('next_service_date')
                    ->orWhereNotNull('next_service_mileage');
            })
            ->latest()
            ->first();
    }
}
