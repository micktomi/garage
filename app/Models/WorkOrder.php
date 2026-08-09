<?php

namespace App\Models;

use App\Enums\ClosureDocument;
use App\Enums\NonIssueReason;
use App\Enums\WorkOrderStatus;
use App\Services\Aade\WorkOrderAadeSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
        'closure_document',
        'non_issue_reason',
    ];

    protected function casts(): array
    {
        return [
            'next_service_date' => 'date',
            'status' => WorkOrderStatus::class,
            'in_shop' => 'boolean',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'closure_document' => ClosureDocument::class,
            'non_issue_reason' => NonIssueReason::class,
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

        // The vehicle is physically here only when in_shop actually ended up
        // true (excludes seeded/imported records created already closed) —
        // that is the real "entered the shop" signal, not creation itself.
        static::created(function (WorkOrder $workOrder) {
            if ($workOrder->in_shop) {
                app(WorkOrderAadeSync::class)->handleCheckedIn($workOrder);
            }
        });

        // non_issue_reason only means anything alongside closure_document=none
        // (ΑΑΔΕ forbids sending it otherwise) — keep it null any other time
        // regardless of which caller set closure_document.
        static::saving(function (WorkOrder $workOrder) {
            if ($workOrder->closure_document !== ClosureDocument::None) {
                $workOrder->non_issue_reason = null;
            }
        });

        // Completing a work order is what triggers the ΑΑΔΕ UpdateClient
        // (entryCompletion=true) call, which requires knowing what document
        // was issued — so it's a hard requirement here too, not just at the
        // UI layer, and for both entry points (workshop controller +
        // Filament) since both just call WorkOrder::update().
        static::updating(function (WorkOrder $workOrder) {
            if ($workOrder->isDirty('status') && $workOrder->status === WorkOrderStatus::Completed) {
                if ($workOrder->closure_document === null) {
                    throw ValidationException::withMessages([
                        'closure_document' => 'Επιλέξτε παραστατικό ολοκλήρωσης πριν κλείσετε την εντολή.',
                    ]);
                }

                if ($workOrder->closure_document === ClosureDocument::None && $workOrder->non_issue_reason === null) {
                    throw ValidationException::withMessages([
                        'non_issue_reason' => 'Επιλέξτε αιτιολογία μη έκδοσης παραστατικού.',
                    ]);
                }
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

        // Only a genuine open -> Completed transition, never a re-save of an
        // already-completed order (wasChanged('status') is false then, so
        // this simply doesn't fire — no separate dedup logic needed here;
        // OutboxManager::enqueue()'s own checksum check is the second layer).
        static::updated(function (WorkOrder $workOrder) {
            if (
                $workOrder->wasChanged('status') &&
                $workOrder->status === WorkOrderStatus::Completed &&
                $workOrder->getRawOriginal('status') !== WorkOrderStatus::Completed->value
            ) {
                app(WorkOrderAadeSync::class)->handleCompleted($workOrder);
            }
        });
    }
}
