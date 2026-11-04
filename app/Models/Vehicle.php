<?php

namespace App\Models;

use App\Models\Concerns\ArchivesInsteadOfDeleting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use ArchivesInsteadOfDeleting;

    protected $fillable = [
        'customer_id',
        'plate_number',
        'make',
        'model',
        'year',
        'fuel',
        'engine_cc',
        'first_registered_at',
        'kteo_expires_at',
        'mileage',
        'vin',
        'notes',
    ];

    protected $casts = [
        'first_registered_at' => 'date',
        'kteo_expires_at' => 'date',
    ];

    public function customer()
    {
        // Archived owners still name their vehicles.
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function hasHistory(): bool
    {
        return $this->workOrders()->withTrashed()->exists()
            || $this->appointments()->exists();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function latestTrackedWorkOrder(): ?WorkOrder
    {
        return $this->workOrders()
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('current_mileage')
                    ->orWhereNotNull('next_service_date')
                    ->orWhereNotNull('next_service_mileage');
            })
            ->latest()
            ->first();
    }
}
