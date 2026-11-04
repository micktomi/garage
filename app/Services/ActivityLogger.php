<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records field-level changes (who, when, record, field, old → new) for the
 * few values that matter after the fact: part prices, work order status and
 * line quantities. Called from model events, so every UI and path is covered.
 * Values are stored as text; numbers are normalised ("9.00" and 9 are equal
 * and not logged as a change).
 */
final class ActivityLogger
{
    /** @param  list<string>  $fields */
    public function created(Model $model, array $fields, ?int $workOrderId = null): void
    {
        foreach ($fields as $field) {
            $this->write($model, $field, null, $model->getAttributes()[$field] ?? null, $workOrderId);
        }
    }

    /**
     * Call from an `updated` listener: the original still holds the old value.
     *
     * @param  list<string>  $fields
     */
    public function updated(Model $model, array $fields, ?int $workOrderId = null): void
    {
        foreach ($fields as $field) {
            $this->write($model, $field, $model->getRawOriginal($field), $model->getAttributes()[$field] ?? null, $workOrderId);
        }
    }

    /** @param  list<string>  $fields */
    public function deleted(Model $model, array $fields, ?int $workOrderId = null): void
    {
        foreach ($fields as $field) {
            $this->write($model, $field, $model->getAttributes()[$field] ?? null, null, $workOrderId);
        }
    }

    private function write(Model $model, string $field, mixed $old, mixed $new, ?int $workOrderId): void
    {
        $old = self::normalise($old);
        $new = self::normalise($new);

        if ($old === $new) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'work_order_id' => $workOrderId,
            'field' => $field,
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }

    private static function normalise(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        // 9, 9.0 and "9.00" are the same price.
        return is_numeric($value) ? (string) (0 + $value) : (string) $value;
    }
}
