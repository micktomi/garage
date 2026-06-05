<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPart extends Model
{
    protected $fillable = [
        'work_order_id',
        'part_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    protected static function booted()
    {
        static::created(function ($workOrderPart) {
            $workOrderPart->part->decrement('quantity', $workOrderPart->quantity);
            $workOrderPart->workOrder->calculatePartsCost();
        });

        static::updated(function ($workOrderPart) {
            if ($workOrderPart->isDirty('part_id')) {
                $oldPartId = $workOrderPart->getOriginal('part_id');
                $oldQuantity = $workOrderPart->getOriginal('quantity');
                $oldPart = Part::find($oldPartId);
                if ($oldPart) {
                    $oldPart->increment('quantity', $oldQuantity);
                }
                $workOrderPart->part->decrement('quantity', $workOrderPart->quantity);
            } else {
                $oldQuantity = $workOrderPart->getOriginal('quantity');
                $diff = $workOrderPart->quantity - $oldQuantity;
                $workOrderPart->part->decrement('quantity', $diff);
            }
            $workOrderPart->workOrder->calculatePartsCost();
        });

        static::deleted(function ($workOrderPart) {
            $workOrderPart->part->increment('quantity', $workOrderPart->quantity);
            $workOrderPart->workOrder->calculatePartsCost();
        });
    }
}
