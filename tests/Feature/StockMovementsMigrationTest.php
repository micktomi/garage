<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockMovementsMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION = 'database/migrations/2026_11_04_000200_create_stock_movements_table.php';

    public function test_existing_stock_gets_one_opening_adjust_movement_per_part(): void
    {
        Artisan::call('migrate:rollback', ['--path' => self::MIGRATION]);
        $now = now()->toDateTimeString();
        DB::table('parts')->insert([
            ['id' => 1, 'name' => 'Με απόθεμα', 'quantity' => 7.5, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 2, 'name' => 'Χωρίς απόθεμα', 'quantity' => 0, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 3, 'name' => 'Αρχειοθετημένο', 'quantity' => 2, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => $now],
        ]);

        Artisan::call('migrate', ['--path' => self::MIGRATION]);

        $rows = DB::table('stock_movements')->orderBy('part_id')->get(['part_id', 'type', 'quantity', 'work_order_id', 'user_id']);
        $this->assertSame([[1, 'adjust', 7.5], [3, 'adjust', 2.0]], $rows->map(fn ($r) => [(int) $r->part_id, $r->type, (float) $r->quantity])->all());
        $this->assertTrue($rows->every(fn ($r) => $r->work_order_id === null && $r->user_id === null));
        $this->artisan('stock:verify')->assertSuccessful();
    }
}
