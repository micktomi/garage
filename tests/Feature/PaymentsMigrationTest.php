<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentsMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION = 'database/migrations/2026_11_04_000400_create_payments_and_completed_at.php';

    public function test_completed_at_is_backfilled_from_checked_out_at_then_updated_at(): void
    {
        Artisan::call('migrate:rollback', ['--path' => self::MIGRATION]);
        DB::table('customers')->insert(['id' => 1, 'full_name' => 'Α', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('vehicles')->insert(['id' => 1, 'customer_id' => 1, 'plate_number' => 'ΑΑΑ1', 'created_at' => now(), 'updated_at' => now()]);
        $row = fn (int $id, string $status, ?string $out, string $updated) => [
            'id' => $id, 'customer_id' => 1, 'vehicle_id' => 1, 'problem_description' => 'x', 'status' => $status,
            'checked_out_at' => $out, 'created_at' => '2026-01-01 08:00:00', 'updated_at' => $updated,
        ];
        DB::table('work_orders')->insert([
            $row(1, 'completed', '2026-03-10 12:00:00', '2026-04-01 09:00:00'),
            $row(2, 'completed', null, '2026-04-02 09:00:00'),
            $row(3, 'in_progress', null, '2026-04-03 09:00:00'),
            $row(4, 'cancelled', '2026-04-04 09:00:00', '2026-04-04 09:00:00'),
        ]);

        Artisan::call('migrate', ['--path' => self::MIGRATION]);

        $this->assertSame([
            1 => '2026-03-10 12:00:00',
            2 => '2026-04-02 09:00:00',
            3 => null,
            4 => null,
        ], DB::table('work_orders')->orderBy('id')->pluck('completed_at', 'id')->all());
        $this->assertSame(0, DB::table('payments')->count());
    }
}
