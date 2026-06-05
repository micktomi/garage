<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'quantity',
        'purchase_price',
        'sale_price',
    ];

    public function workOrderParts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }
}
