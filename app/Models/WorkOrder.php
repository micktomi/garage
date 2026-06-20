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
        $this->parts_cost = $this->workOrderParts()->selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0;
        $this->total_cost = $this->parts_cost + $this->labor_cost;
        $this->saveQuietly();
    }

    protected static function booted(): void
    {
        static::updated(function (WorkOrder $workOrder) {
            if (
                $workOrder->wasChanged('status') &&
                $workOrder->status === 'cancelled' &&
                $workOrder->getOriginal('status') !== 'cancelled'
            ) {
                foreach ($workOrder->workOrderParts as $workOrderPart) {
                    if ($workOrderPart->part) {
                        $workOrderPart->part->increment('quantity', $workOrderPart->quantity);
                    }
                }
            }
        });
    }
}
