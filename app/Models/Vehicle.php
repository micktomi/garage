<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'customer_id',
        'plate_number',
        'make',
        'model',
        'year',
        'mileage',
        'vin',
        'notes',
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
}
