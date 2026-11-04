<?php

namespace App\Models;

use App\Models\Concerns\ArchivesInsteadOfDeleting;
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
