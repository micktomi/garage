<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\WorkOrderStatus;
use App\Models\Payment;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PaymentService
{
    /**
     * Records a payment. Refused when the amount is not positive, the order
     * is cancelled, or the amount exceeds the outstanding balance.
     *
     * The balance check and the insert run after a write on the order row, so
     * two payments for the same order are serialized (SQLite takes its write
     * lock there; MySQL/Postgres lock the row) and cannot both pass.
     */
    public function record(WorkOrder $order, float $amount, PaymentMethod $method, ?Carbon $paidAt = null, ?string $note = null): Payment
    {
        $cents = (int) round($amount * 100);

        if ($cents <= 0) {
            throw ValidationException::withMessages(['amount' => 'Το ποσό πρέπει να είναι μεγαλύτερο του μηδενός.']);
        }

        return DB::transaction(function () use ($order, $cents, $method, $paidAt, $note) {
            DB::table('work_orders')->where('id', $order->getKey())->update(['updated_at' => DB::raw('updated_at')]);
            $fresh = WorkOrder::withTrashed()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status === WorkOrderStatus::Cancelled) {
                throw ValidationException::withMessages(['amount' => 'Η εντολή είναι ακυρωμένη: δεν δέχεται πληρωμές.']);
            }

            $balanceCents = $fresh->balanceCents();

            if ($cents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Το ποσό υπερβαίνει το υπόλοιπο της εντολής ('.self::money(max(0, $balanceCents)).').',
                ]);
            }

            return Payment::create([
                'work_order_id' => $fresh->getKey(),
                'amount' => $cents / 100,
                'method' => $method,
                'paid_at' => $paidAt ?? now(),
                'user_id' => Auth::id(),
                'note' => $note,
            ]);
        });
    }

    public static function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }
}
