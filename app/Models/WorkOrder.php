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
        'current_mileage',
        'next_service_date',
        'next_service_mileage',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'next_service_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function workOrderParts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function calculatePartsCost()
    {
        $this->parts_cost = $this->workOrderParts()->sum('line_total');
        $this->total_cost = $this->parts_cost + $this->labor_cost;
        $this->saveQuietly();
    }
}
