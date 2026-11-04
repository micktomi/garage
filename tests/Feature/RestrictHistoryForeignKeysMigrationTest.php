<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Runs the FK migration against data written under the old (cascade) schema.
 * DatabaseMigrations, not RefreshDatabase: the migration manages its own
 * transaction and PRAGMA foreign_keys, which must not run inside a test
 * transaction.
 */
class RestrictHistoryForeignKeysMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION = '2026_11_04_000100_restrict_history_foreign_keys';

    /** @var array<string, list<array{string, string}>> table => [[column, on_delete]] */
    private const EXPECTED = [
        'vehicles' => [['customer_id', 'RESTRICT']],
        'appointments' => [['customer_id', 'RESTRICT'], ['vehicle_id', 'RESTRICT']],
        'work_orders' => [['customer_id', 'RESTRICT'], ['vehicle_id', 'RESTRICT']],
        'work_order_parts' => [['part_id', 'RESTRICT'], ['work_order_id', 'RESTRICT']],
    ];

    private function onDelete(string $table): array
    {
        return collect(DB::select("PRAGMA foreign_key_list('{$table}')"))
            ->map(fn ($fk) => [$fk->from, strtoupper($fk->on_delete)])
            ->sortBy(0)->values()->all();
    }

    public function test_migration_switches_history_foreign_keys_to_restrict_and_keeps_all_rows(): void
    {
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/'.self::MIGRATION.'.php']);
        $this->assertSame('CASCADE', $this->onDelete('vehicles')[0][1], 'precondition: old schema cascades');

        $now = now()->toDateTimeString();
        DB::table('customers')->insert([['id' => 1, 'full_name' => 'Α', 'afm' => '123456789', 'created_at' => $now, 'updated_at' => $now], ['id' => 2, 'full_name' => 'Β', 'afm' => null, 'created_at' => $now, 'updated_at' => $now]]);
        DB::table('vehicles')->insert([['id' => 1, 'customer_id' => 1, 'plate_number' => 'ΑΑΑ1111', 'created_at' => $now, 'updated_at' => $now], ['id' => 2, 'customer_id' => 2, 'plate_number' => 'ΒΒΒ2222', 'created_at' => $now, 'updated_at' => $now]]);
        DB::table('parts')->insert(['id' => 1, 'name' => 'Φίλτρο', 'quantity' => 3, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('work_orders')->insert(['id' => 7, 'customer_id' => 1, 'vehicle_id' => 1, 'problem_description' => 'x', 'status' => 'completed', 'labor_cost' => 10, 'parts_cost' => 18, 'total_cost' => 28, 'idempotency_key' => 'k1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('work_order_parts')->insert(['work_order_id' => 7, 'part_id' => 1, 'source' => 'from_stock', 'quantity' => 2, 'unit_price' => 9, 'line_total' => 18, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('appointments')->insert(['customer_id' => 2, 'vehicle_id' => 2, 'appointment_date' => $now, 'status' => 'scheduled', 'created_at' => $now, 'updated_at' => $now]);
        $before = $this->snapshot();

        Artisan::call('migrate', ['--path' => 'database/migrations/'.self::MIGRATION.'.php']);

        $this->assertSame($before, $this->snapshot(), 'rows changed during the rebuild');
        foreach (self::EXPECTED as $table => $expected) {
            $this->assertSame($expected, $this->onDelete($table), "{$table} foreign keys");
        }
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'), 'foreign keys must be re-enabled');

        // Unique indexes survived the rebuild.
        $this->expectException(QueryException::class);
        DB::table('work_orders')->insert(['customer_id' => 1, 'vehicle_id' => 1, 'problem_description' => 'dup', 'idempotency_key' => 'k1', 'created_at' => $now, 'updated_at' => $now]);
    }

    public function test_rollback_restores_the_previous_cascade_definitions(): void
    {
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/'.self::MIGRATION.'.php']);

        $this->assertSame([['customer_id', 'CASCADE']], $this->onDelete('vehicles'));
        $this->assertSame([['part_id', 'CASCADE'], ['work_order_id', 'CASCADE']], $this->onDelete('work_order_parts'));
        $this->assertSame(1, (int) DB::scalar('PRAGMA foreign_keys'));
    }

    private function snapshot(): array
    {
        $tables = ['customers', 'vehicles', 'parts', 'work_orders', 'work_order_parts', 'appointments'];

        return collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all()])->all();
    }
}
