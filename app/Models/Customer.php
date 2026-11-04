<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use App\Models\Concerns\ArchivesInsteadOfDeleting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use ArchivesInsteadOfDeleting;

    protected $fillable = [
        'full_name',
        'afm',
        'phone',
        'email',
        'address',
        'notes',
    ];

    public function hasHistory(): bool
    {
        return $this->vehicles()->withTrashed()->exists()
            || $this->workOrders()->withTrashed()->exists()
            || $this->appointments()->exists();
    }

    /** Sum of what completed (non-archived) orders still owe; overpayments count as credit. */
    public function balance(): float
    {
        return round((float) static::withTrashed()->withBalance()->whereKey($this->getKey())->value('balance'), 2);
    }

    public function scopeWithBalance(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select($query->getModel()->getTable().'.*');
        }

        return $query->addSelect(['balance' => WorkOrder::query()
            ->selectRaw('ROUND(COALESCE(SUM(total_cost - COALESCE((SELECT SUM(amount) FROM payments WHERE payments.work_order_id = work_orders.id), 0)), 0), 2)')
            ->whereColumn('work_orders.customer_id', 'customers.id')
            ->where('status', WorkOrderStatus::Completed->value),
        ]);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
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
