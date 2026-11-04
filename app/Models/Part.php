<?php

namespace App\Models;

use App\Models\Concerns\ArchivesInsteadOfDeleting;
use App\Services\ActivityLogger;
use App\Services\StockService;
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
        // Stock movements are history too: a part that ever held stock is only archived.
        return $this->workOrderParts()->exists()
            || $this->stockMovements()->exists();
    }

    public function workOrderParts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    protected static function booted(): void
    {
        static::creating(fn (Part $part) => StockService::assertNotNegative((float) $part->quantity));

        static::created(fn (Part $part) => app(StockService::class)->recordOpening($part));

        static::updated(fn (Part $part) => app(ActivityLogger::class)->updated($part, ['purchase_price', 'sale_price']));

        // Editing the quantity directly (Filament form, tinker) is an
        // adjustment through StockService, never a silent overwrite.
        static::updating(function (Part $part) {
            if (! $part->isDirty('quantity')) {
                return;
            }

            $target = (float) $part->quantity;
            $part->quantity = $part->getOriginal('quantity');
            app(StockService::class)->setQuantity($part, $target);

            $part->quantity = static::withTrashed()->whereKey($part->getKey())->value('quantity');
            $part->syncOriginalAttribute('quantity');
        });
    }
}
