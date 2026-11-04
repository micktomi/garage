<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * delete() archives (soft delete). forceDelete() is refused while the record
 * still has history, so a mistaken click can never take work orders, part
 * lines or appointments with it. The database enforces the same rule with
 * ON DELETE RESTRICT foreign keys.
 */
trait ArchivesInsteadOfDeleting
{
    use SoftDeletes;

    /** True when other records (including archived ones) still reference this one. */
    abstract public function hasHistory(): bool;

    public static function bootArchivesInsteadOfDeleting(): void
    {
        static::forceDeleting(fn (self $model) => $model->hasHistory() ? false : null);
    }
}
