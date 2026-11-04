<?php

namespace App\Models;

use App\Models\Concerns\ArchivesInsteadOfDeleting;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use ArchivesInsteadOfDeleting;

    protected $fillable = [
        'code',
        'name',
        'description',
        'quantity',
        'purchase_price',
        'sale_price',
    ];

    public function hasHistory(): bool
    {
        return $this->workOrderParts()->exists();
    }

    public function workOrderParts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }
}
