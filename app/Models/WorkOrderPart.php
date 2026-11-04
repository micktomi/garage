<?php

namespace App\Models;

use App\Services\ActivityLogger;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
        return $this->belongsTo(WorkOrder::class)->withTrashed();
    }

    public function part(): BelongsTo
    {
        // Lines of closed orders keep naming a part after it is archived.
        return $this->belongsTo(Part::class)->withTrashed();
    }

    public function displayName(): string
    {
        if ($this->source === 'from_stock') {
            return $this->part?->name ?? $this->description ?? '-';
        }

        return $this->description ?? '-';
    }

    /**
     * Writing a line and moving its stock is one unit: if the stock move is
     * refused (not enough stock) the line write is rolled back with it.
     */
    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return DB::transaction(fn () => parent::delete());
    }

    /** @return array{source: ?string, part_id: ?int, quantity: mixed} */
    private function stockState(bool $original): array
    {
        $read = fn (string $key) => $original ? $this->getOriginal($key) : $this->getAttribute($key);

        return ['source' => $read('source'), 'part_id' => $read('part_id'), 'quantity' => $read('quantity')];
    }

    protected static function booted()
    {
        // Stock arithmetic lives in StockService; these hooks only report what changed.
        static::created(function (WorkOrderPart $line) {
            app(StockService::class)->syncLine($line, null, $line->stockState(false));
            app(ActivityLogger::class)->created($line, ['quantity'], $line->work_order_id);
            $line->workOrder->calculatePartsCost();
        });

        static::updated(function (WorkOrderPart $line) {
            app(StockService::class)->syncLine($line, $line->stockState(true), $line->stockState(false));
            app(ActivityLogger::class)->updated($line, ['quantity'], $line->work_order_id);
            $line->workOrder->calculatePartsCost();
        });

        static::deleted(function (WorkOrderPart $line) {
            app(StockService::class)->syncLine($line, $line->stockState(false), null);
            app(ActivityLogger::class)->deleted($line, ['quantity'], $line->work_order_id);
            $line->workOrder->calculatePartsCost();
        });
    }
}
