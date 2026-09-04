<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
