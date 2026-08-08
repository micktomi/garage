<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
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
        'in_shop',
        'checked_in_at',
        'checked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'next_service_date' => 'date',
            'status' => WorkOrderStatus::class,
            'in_shop' => 'boolean',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    /**
     * Vehicles physically on the shop floor right now.
     *
     * The status filter is part of the definition, not a safety net: a closed
     * order's vehicle has left by definition, whatever the flag still says.
     */
    public function scopeInShop($query)
    {
        return $query
            ->whereIn('status', WorkOrderStatus::openValues())
            ->where('in_shop', true);
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
        // A new order means the vehicle was just handed over at the counter.
        // Records seeded or imported as already closed are the exception, so
        // presence follows the status unless the caller states otherwise.
        static::creating(function (WorkOrder $workOrder) {
            $workOrder->checked_in_at ??= now();

            // A missing status falls back to the column default, which is open.
            if (! $workOrder->isDirty('in_shop')) {
                $workOrder->in_shop = $workOrder->status?->isOpen() ?? true;
            }

            if (! $workOrder->in_shop) {
                $workOrder->checked_out_at ??= $workOrder->checked_in_at;
            }
        });

        // Closing an order also ends the vehicle's stay, so the shop-floor
        // flag never outlives the work it describes.
        static::updating(function (WorkOrder $workOrder) {
            if ($workOrder->isDirty('status') && ! $workOrder->status->isOpen() && $workOrder->in_shop) {
                $workOrder->in_shop = false;
                $workOrder->checked_out_at ??= now();
            }
        });

        static::updated(function (WorkOrder $workOrder) {
            if (
                $workOrder->wasChanged('status') &&
                $workOrder->status === WorkOrderStatus::Cancelled &&
                $workOrder->getRawOriginal('status') !== WorkOrderStatus::Cancelled->value
            ) {
                foreach ($workOrder->workOrderParts as $workOrderPart) {
                    if ($workOrderPart->source === 'from_stock' && $workOrderPart->part) {
                        $workOrderPart->part->increment('quantity', $workOrderPart->quantity);
                    }
                }
            }
        });
    }
}
