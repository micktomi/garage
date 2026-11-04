<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\WorkOrderStatus;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only code that changes parts.quantity. Every change writes one
 * stock_movements row in the same transaction, so the quantity always equals
 * the sum of the part's movements (checked by `php artisan stock:verify`).
 *
 * Stock can never go negative: a consumption is one conditional UPDATE
 * (`... WHERE quantity >= ?`), so the availability check and the decrement
 * cannot be separated by a competing request.
 */
final class StockService
{
    /**
     * A part was just inserted with an opening quantity: record it as stock
     * entering the shop. The quantity itself is already on the row.
     */
    public function recordOpening(Part $part): void
    {
        $quantity = round((float) $part->quantity, 1);

        if ($quantity != 0.0) {
            $this->record($part->getKey(), $quantity, StockMovementType::In);
        }
    }

    /** Manual correction to a counted quantity. */
    public function setQuantity(Part $part, float $target, ?string $note = null): void
    {
        self::assertNotNegative($target);

        DB::transaction(function () use ($part, $target, $note) {
            $current = (float) Part::withTrashed()->whereKey($part->getKey())->value('quantity');
            $this->move($part->getKey(), $target - $current, StockMovementType::Adjust, note: $note);
        });
    }

    /**
     * A work order line was created, changed or deleted. $before / $after are
     * the line's stock-relevant state (source, part_id, quantity), null for a
     * line that did not exist / no longer exists. Lines of a cancelled order
     * hold no stock, so they move nothing.
     *
     * @param  array{source: ?string, part_id: ?int, quantity: float|string|null}|null  $before
     * @param  array{source: ?string, part_id: ?int, quantity: float|string|null}|null  $after
     */
    public function syncLine(WorkOrderPart $line, ?array $before, ?array $after): void
    {
        // Raw column (toBase): Eloquent's value() would return the cast enum.
        $status = WorkOrder::withTrashed()->whereKey($line->work_order_id)->toBase()->value('status');

        if ($status === null || $status === WorkOrderStatus::Cancelled->value) {
            return;
        }

        $net = [];
        foreach ([[$before, 1], [$after, -1]] as [$state, $sign]) {
            if (($state['source'] ?? null) === 'from_stock' && filled($state['part_id'] ?? null)) {
                $partId = (int) $state['part_id'];
                $net[$partId] = ($net[$partId] ?? 0) + $sign * (float) $state['quantity'];
            }
        }

        // Returns before consumptions, so swapping parts never fails spuriously.
        arsort($net);

        DB::transaction(function () use ($net, $line) {
            foreach ($net as $partId => $delta) {
                $this->move($partId, $delta, StockMovementType::Out, $line->work_order_id);
            }
        });
    }

    /** Cancelling gives every stock line back; reopening takes it again. */
    public function orderStatusChanged(WorkOrder $order, string $previousStatus): void
    {
        $wasCancelled = $previousStatus === WorkOrderStatus::Cancelled->value;
        $isCancelled = $order->status === WorkOrderStatus::Cancelled;

        if ($wasCancelled === $isCancelled) {
            return;
        }

        DB::transaction(function () use ($order, $isCancelled) {
            $lines = $order->workOrderParts()
                ->where('source', 'from_stock')
                ->whereNotNull('part_id')
                ->get(['part_id', 'quantity']);

            foreach ($lines as $line) {
                $this->move(
                    $line->part_id,
                    $isCancelled ? (float) $line->quantity : -(float) $line->quantity,
                    $isCancelled ? StockMovementType::Cancel : StockMovementType::Reopen,
                    $order->getKey(),
                );
            }
        });
    }

    /**
     * What a form may consume of a part: current stock plus what the order
     * being edited already holds of it (that will be given back on save).
     */
    public function availableFor(int $partId, ?WorkOrder $order = null): float
    {
        $available = (float) Part::withTrashed()->whereKey($partId)->value('quantity');

        if ($order?->exists && $order->status !== WorkOrderStatus::Cancelled) {
            $available += (float) $order->workOrderParts()
                ->where('source', 'from_stock')
                ->where('part_id', $partId)
                ->sum('quantity');
        }

        return round($available, 1);
    }

    public static function assertNotNegative(float $quantity): void
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Η ποσότητα αποθέματος δεν μπορεί να είναι αρνητική.',
            ]);
        }
    }

    private function move(int $partId, float $delta, StockMovementType $type, ?int $workOrderId = null, ?string $note = null): void
    {
        $delta = round($delta, 1);

        if ($delta == 0.0) {
            return;
        }

        DB::transaction(function () use ($partId, $delta, $type, $workOrderId, $note) {
            $query = Part::withTrashed()->whereKey($partId);

            if ($delta < 0) {
                $query->where('quantity', '>=', -$delta);
            }

            if ($query->increment('quantity', $delta) === 0) {
                $available = Part::withTrashed()->whereKey($partId)->value('quantity');

                throw ValidationException::withMessages([
                    'quantity' => 'Ανεπαρκές απόθεμα. Διαθέσιμο: '.(float) $available,
                ]);
            }

            $this->record($partId, $delta, $type, $workOrderId, $note);
        });
    }

    private function record(int $partId, float $quantity, StockMovementType $type, ?int $workOrderId = null, ?string $note = null): void
    {
        StockMovement::create([
            'part_id' => $partId,
            'quantity' => $quantity,
            'type' => $type,
            'work_order_id' => $workOrderId,
            'user_id' => Auth::id(),
            'note' => $note,
        ]);
    }
}
