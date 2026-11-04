<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Foreign keys that point at history switch from ON DELETE CASCADE to
 * ON DELETE RESTRICT: the database itself refuses to hard-delete a customer,
 * vehicle, work order or part that other records still reference.
 *
 * SQLite cannot alter a foreign key in place. Laravel rebuilds each child
 * table (create __temp__ table → copy rows → drop original → rename), keeping
 * columns, rows and indexes. The dangerous step is "drop original": while
 * PRAGMA foreign_keys is ON, SQLite runs an implicit DELETE first, and the old
 * CASCADE rules would wipe the children of the table being rebuilt (dropping
 * `vehicles` would delete every work order and appointment).
 *
 * PRAGMA foreign_keys is silently ignored inside a transaction, so this
 * migration opts out of the migrator's transaction, switches the pragma off
 * first, verifies it really is off, and only then opens its own transaction.
 * Before committing it checks that no table lost or gained a row and that
 * PRAGMA foreign_key_check reports no dangling references; any mismatch
 * rolls everything back.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /** child table => [column => parent table] */
    private const KEYS = [
        'vehicles' => ['customer_id' => 'customers'],
        'appointments' => ['customer_id' => 'customers', 'vehicle_id' => 'vehicles'],
        'work_orders' => ['customer_id' => 'customers', 'vehicle_id' => 'vehicles'],
        'work_order_parts' => ['work_order_id' => 'work_orders', 'part_id' => 'parts'],
    ];

    private const COUNTED = ['customers', 'vehicles', 'appointments', 'work_orders', 'work_order_parts', 'parts'];

    public function up(): void
    {
        $this->rebuild('restrict');
    }

    public function down(): void
    {
        $this->rebuild('cascade');
    }

    private function rebuild(string $onDelete): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';

        if ($sqlite) {
            DB::statement('PRAGMA foreign_keys = OFF');

            if ((int) DB::scalar('PRAGMA foreign_keys') !== 0) {
                throw new RuntimeException('Could not disable SQLite foreign keys (is a transaction open?). Aborting before any table is rebuilt.');
            }
        }

        try {
            DB::transaction(function () use ($onDelete, $sqlite) {
                $before = $this->rowCounts();

                foreach (self::KEYS as $table => $keys) {
                    Schema::table($table, function (Blueprint $blueprint) use ($keys, $onDelete) {
                        foreach ($keys as $column => $parent) {
                            $blueprint->dropForeign([$column]);
                            $blueprint->foreign($column)->references('id')->on($parent)->onDelete($onDelete);
                        }
                    });
                }

                if ($this->rowCounts() !== $before) {
                    throw new RuntimeException('Row counts changed while rebuilding foreign keys. Rolled back.');
                }

                if ($sqlite && DB::select('PRAGMA foreign_key_check') !== []) {
                    throw new RuntimeException('PRAGMA foreign_key_check found dangling references. Rolled back.');
                }
            });
        } finally {
            if ($sqlite) {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    /** @return array<string, int> */
    private function rowCounts(): array
    {
        return collect(self::COUNTED)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
    }
};
