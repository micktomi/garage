<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = [
        'customer_id',
        'vehicle_id',
        'problem_description',
        'diagnosis',
        'work_performed',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
