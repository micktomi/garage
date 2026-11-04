<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Concerns\ArchivesInsteadOfDeleting;
use App\Services\ActivityLogger;
use App\Services\PaymentService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrder extends Model
{
    use ArchivesInsteadOfDeleting;

    private const STALE_LOCK_MESSAGE = 'Η εντολή άλλαξε από άλλον χρήστη ενώ ήταν ανοιχτή αυτή η σελίδα. Ανανεώστε τη σελίδα και δείτε την τρέχουσα κατάσταση πριν αποθηκεύσετε ξανά.';

    /**
     * Set only for the duration of a updateWithExpectedVersion() call; tells
     * performUpdate() below to add the compare-and-swap condition. Every
     * other save() on this model (Filament's own create flow, the AI
     * assistant, calculatePartsCost(), ...) is unaffected.
     */
    private ?int $expectedLockVersion = null;

    protected $fillable = [
        'idempotency_key',
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

    /**
     * The column default alone is not enough: a freshly created model would
     * carry a null lock_version in memory until something re-read it, and the
     * pages that render the token do so straight off the instance they just
     * saved.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'lock_version' => 0,
    ];

    protected function casts(): array
    {
        return [
            'next_service_date' => 'date',
            'completed_at' => 'datetime',
            'status' => WorkOrderStatus::class,
            'in_shop' => 'boolean',
            'lock_version' => 'integer',
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
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paidCents(): int
    {
        // Uses withSum('payments', 'amount') when the list query loaded it.
        $sum = array_key_exists('payments_sum_amount', $this->getAttributes())
            ? $this->getAttributes()['payments_sum_amount']
            : $this->payments()->sum('amount');

        return (int) round((float) $sum * 100);
    }

    public function balanceCents(): int
    {
        return (int) round((float) $this->total_cost * 100) - $this->paidCents();
    }

    public function paidAmount(): float
    {
        return $this->paidCents() / 100;
    }

    /** Negative when the customer paid more than the order now costs. */
    public function balance(): float
    {
        return $this->balanceCents() / 100;
    }

    public function paymentStatus(): PaymentStatus
    {
        return PaymentStatus::fromCents((int) round((float) $this->total_cost * 100), $this->paidCents());
    }

    public function paymentStatusLabel(): string
    {
        $status = $this->paymentStatus();

        return $status === PaymentStatus::Overpaid
            ? $status->label().' '.PaymentService::money(-$this->balanceCents())
            : $status->label();
    }

    public function daysSinceCompletion(): ?int
    {
        return $this->completed_at
            ? (int) $this->completed_at->copy()->startOfDay()->diffInDays(now()->startOfDay())
            : null;
    }

    /** Completed orders with something still to pay: the «Οφειλές». */
    public function scopeWithDebt(Builder $query): Builder
    {
        return $query
            ->where('status', WorkOrderStatus::Completed->value)
            ->whereRaw('ROUND(total_cost - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.work_order_id = work_orders.id), 0), 2) > 0');
    }

    /** A work order is history in itself: it can be archived, never force-deleted. */
    public function hasHistory(): bool
    {
        return true;
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

    /**
     * Optimistic-concurrency update for an edit that was rendered from an
     * earlier version of this record: the staleness check and the write are
     * the same SQL statement (`WHERE id = ? AND lock_version = ?`, with the
     * version bump in that statement's own SET clause via the `updating`
     * hook below) — not a read, then a separate write. Two requests racing
     * on this row can never both succeed: whichever commits first moves
     * lock_version, so the loser's WHERE clause matches zero rows.
     *
     * `lock_version` is never fillable, so the only way a request can supply
     * one is the explicit argument here — a caller that forgets cannot
     * accidentally pass the check.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException when $expectedVersion no longer matches
     *                             the row's current lock_version
     */
    public function updateWithExpectedVersion(int $expectedVersion, array $attributes): void
    {
        $this->fill($attributes);
        // Even an unchanged edit must verify its rendered version atomically.
        $this->setAttribute('lock_version', (int) $this->getRawOriginal('lock_version') + 1);
        $this->expectedLockVersion = $expectedVersion;

        try {
            $this->save();
        } finally {
            $this->expectedLockVersion = null;
        }
    }

    /**
     * Identical to Eloquent's own performUpdate() (see the parent class)
     * except that, only when updateWithExpectedVersion() started this save,
     * the UPDATE carries an extra `lock_version = ?` condition and checks
     * how many rows it actually matched. Every other caller falls straight
     * through to the ordinary parent behaviour, unchanged.
     */
    protected function performUpdate(Builder $query)
    {
        if ($this->expectedLockVersion === null) {
            return parent::performUpdate($query);
        }

        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirtyForUpdate();

        if (count($dirty) > 0) {
            $affected = $this->setKeysForSaveQuery($query)
                ->where('lock_version', $this->expectedLockVersion)
                ->update($dirty);

            if ($affected === 0) {
                throw ValidationException::withMessages(['lock_version' => self::STALE_LOCK_MESSAGE]);
            }

            $this->syncChanges();

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /** Reconcile allocation once per persisted cancellation boundary, atomically. */
    public function save(array $options = [])
    {
        if (! $this->exists || ! $this->isDirty('status')) {
            return parent::save($options);
        }

        return DB::transaction(function () use ($options) {
            $previous = static::withTrashed()->whereKey($this->getKey())->lockForUpdate()->firstOrFail()->getRawOriginal('status');
            $saved = parent::save($options);
            if (! $saved || $previous === $this->status->value) {
                return $saved;
            }

            app(StockService::class)->orderStatusChanged($this, $previous);

            return $saved;
        });
    }

    protected static function booted(): void
    {
        // Registered before every other updating hook so the version advances
        // even when a later hook rewrites more fields. calculatePartsCost()
        // uses saveQuietly(), which fires no model events — so recomputing
        // totals deliberately does not invalidate an open page.
        static::updating(function (WorkOrder $workOrder) {
            $workOrder->lock_version = (int) $workOrder->getRawOriginal('lock_version') + 1;
        });

        // The balance becomes a debt from this moment; reopening withdraws it.
        static::updating(function (WorkOrder $workOrder) {
            if ($workOrder->isDirty('status')) {
                $workOrder->completed_at = $workOrder->status === WorkOrderStatus::Completed ? now() : null;
            }
        });

        static::updated(fn (WorkOrder $workOrder) => app(ActivityLogger::class)->updated($workOrder, ['status'], $workOrder->getKey()));

        // A new order means the vehicle was just handed over at the counter.
        // Records seeded or imported as already closed are the exception, so
        // presence follows the status unless the caller states otherwise.
        static::creating(function (WorkOrder $workOrder) {
            $workOrder->checked_in_at ??= now();

            if ($workOrder->status === WorkOrderStatus::Completed) {
                $workOrder->completed_at ??= now();
            }

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

    }
}
