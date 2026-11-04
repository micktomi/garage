<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Created only through PaymentService (balance check). Immutable: a wrong
 * payment is deleted by the owner (logged) and entered again.
 */
class Payment extends Model
{
    protected $fillable = ['work_order_id', 'amount', 'method', 'paid_at', 'user_id', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Payments are immutable; delete and record a new one.');
        });

        static::deleted(fn (Payment $payment) => app(ActivityLogger::class)
            ->deleted($payment, ['amount', 'method', 'paid_at'], $payment->work_order_id));
    }
}
