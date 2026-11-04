<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * garage:purge-business-data is run by hand before demos.
 */
class PurgeBusinessDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_purged_work_orders_id_is_never_handed_out_again(): void
    {
        $purged = $this->createWorkOrder('ΠΡΝ-1000');
        $purgedId = $purged->id;

        $this->artisan('garage:purge-business-data', ['--force' => true])->assertSuccessful();

        $fresh = $this->createWorkOrder('ΜΕΤ-1000');

        $this->assertNotSame($purgedId, $fresh->id, 'A purged work order id must never be handed out again.');
    }

    public function test_purge_still_removes_the_business_data_it_is_meant_to_remove(): void
    {
        $this->createWorkOrder('ΚΑΘ-1000');

        $this->artisan('garage:purge-business-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, WorkOrder::count());
        $this->assertSame(0, Vehicle::count());
        $this->assertSame(0, Customer::count());
    }

    /**
     * Runs with foreign keys effectively ON (the test transaction makes the
     * command's PRAGMA a no-op), i.e. the strict case for the RESTRICT keys:
     * the command must still empty every table, archived rows included.
     */
    public function test_purge_removes_archived_rows_and_part_lines_under_restrict_foreign_keys(): void
    {
        $user = User::factory()->create();
        $order = $this->createWorkOrder('ΑΡΧ-1000');
        $part = Part::create(['name' => 'Φίλτρο', 'quantity' => 5]);
        WorkOrderPart::create(['work_order_id' => $order->id, 'part_id' => $part->id, 'source' => 'from_stock', 'quantity' => 1, 'unit_price' => 9, 'line_total' => 9]);
        Appointment::create(['customer_id' => $order->customer_id, 'vehicle_id' => $order->vehicle_id, 'appointment_date' => now()->addDay(), 'status' => 'scheduled']);
        $active = $this->createWorkOrder('ΕΝΕ-1000');
        $order->delete();
        $order->vehicle->delete();
        $order->customer->delete();
        $part->delete();
        $this->assertSame('RESTRICT', strtoupper(collect(DB::select("PRAGMA foreign_key_list('work_order_parts')"))->firstWhere('from', 'part_id')->on_delete));

        $this->artisan('garage:purge-business-data', ['--force' => true])->assertSuccessful();

        foreach (['activity_logs', 'stock_movements', 'work_order_parts', 'work_orders', 'appointments', 'vehicles', 'customers', 'parts'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} still has rows (archived rows must be purged too)");
        }
        $this->assertNotNull($user->fresh(), 'users must survive the purge');
        $this->assertNull(WorkOrder::withTrashed()->find($active->id));
    }

    private function createWorkOrder(string $plate): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Πελάτης '.$plate]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
        ]);

        return WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => WorkOrderStatus::New,
        ]);
    }
}
