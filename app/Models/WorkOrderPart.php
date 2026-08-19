<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class WorkOrderPart extends Model
{
    protected $fillable = [
        'work_order_id',
        'part_id',
        'source',
        'description',
        'quantity',
        'unit_cost',
        'unit_price',
        'line_total',
        'note',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function displayName(): string
    {
        if ($this->source === 'from_stock') {
            return $this->part?->name ?? $this->description ?? '-';
        }

        return $this->description ?? '-';
    }

    /**
     * Stock consumption as a single conditional UPDATE: the "is there enough"
     * check and the decrement are one statement, so two rows of the same part
     * — whether in one order, in two browser tabs, or in two requests — can
     * never both pass. Neither UI's own per-row form rule can see the other
     * consumption, which is why this lives here rather than in a controller.
     */
    private static function consumeStock(Part $part, float $quantity): void
    {
        if ($quantity <= 0) {
            // Returning stock, not consuming it — no availability to check.
            $part->decrement('quantity', $quantity);

            return;
        }

        $consumed = Part::query()
            ->whereKey($part->getKey())
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        if ($consumed === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Ανεπαρκές απόθεμα. Διαθέσιμο: '.$part->fresh()?->quantity,
            ]);
        }
    }

    protected static function booted()
    {
        static::created(function ($workOrderPart) {
            if ($workOrderPart->source === 'from_stock' && $workOrderPart->part_id) {
                self::consumeStock($workOrderPart->part, $workOrderPart->quantity);
            }
            $workOrderPart->workOrder->calculatePartsCost();
        });

        static::updated(function ($workOrderPart) {
            if ($workOrderPart->source === 'from_stock' && $workOrderPart->part_id) {
                if ($workOrderPart->isDirty('part_id')) {
                    $oldPartId = $workOrderPart->getOriginal('part_id');
                    $oldQuantity = $workOrderPart->getOriginal('quantity');
                    $oldPart = Part::find($oldPartId);
                    if ($oldPart) {
                        $oldPart->increment('quantity', $oldQuantity);
                    }
                    self::consumeStock($workOrderPart->part, $workOrderPart->quantity);
                } else {
                    $oldQuantity = $workOrderPart->getOriginal('quantity');
                    $diff = $workOrderPart->quantity - $oldQuantity;
                    self::consumeStock($workOrderPart->part, $diff);
                }
            }
            $workOrderPart->workOrder->calculatePartsCost();
        });

        static::deleted(function ($workOrderPart) {
            if ($workOrderPart->source === 'from_stock' && $workOrderPart->part_id) {
                $workOrderPart->part->increment('quantity', $workOrderPart->quantity);
            }
            $workOrderPart->workOrder->calculatePartsCost();
        });
    }
}
