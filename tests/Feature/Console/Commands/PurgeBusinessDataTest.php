<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Outbox\Models\Transmission;
use Micktomi\GarageAadeBridge\Outbox\OutboxManager;
use Tests\TestCase;

/**
 * garage:purge-business-data is run by hand before demos, on a database that
 * may already have talked to ΑΑΔΕ. The invariant it must never break: a new
 * work order can never inherit the ΑΑΔΕ identity (dclId) of a purged one.
 */
class PurgeBusinessDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_work_order_created_after_a_purge_cannot_inherit_the_purged_orders_dcl_id(): void
    {
        $purged = $this->createWorkOrder('ΠΡΝ-1000');
        $this->markSendClientAsSent($purged, 100000000830764);
        $purgedId = $purged->id;

        $this->assertSame(
            100000000830764,
            app(OutboxManager::class)->resolveDclId('work_order', $purgedId),
        );

        $this->artisan('garage:purge-business-data', ['--force' => true])->assertSuccessful();

        $fresh = $this->createWorkOrder('ΜΕΤ-1000');

        $this->assertNotSame($purgedId, $fresh->id, 'A purged work order id must never be handed out again.');
        $this->assertNull(
            app(OutboxManager::class)->resolveDclId('work_order', $fresh->id),
            'A new work order must not resolve a dclId that belonged to a purged one.',
        );
    }

    public function test_purge_clears_the_aade_outbox_and_transmission_history(): void
    {
        $workOrder = $this->createWorkOrder('ΙΣΤ-1000');
        $entry = $this->markSendClientAsSent($workOrder, 100000000830764);

        Transmission::create([
            'outbox_id' => $entry->id,
            'correlation_id' => $entry->correlation_id,
            'attempt_number' => 1,
            'endpoint' => 'SendClient',
            'http_method' => 'POST',
            'outcome' => 'success',
            'remote_identifier' => 100000000830764,
            'response_status_code' => 'Success',
        ]);

        $this->artisan('garage:purge-business-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, OutboxEntry::count());
        $this->assertSame(0, Transmission::count());
    }

    public function test_purge_still_removes_the_business_data_it_is_meant_to_remove(): void
    {
        $this->createWorkOrder('ΚΑΘ-1000');

        $this->artisan('garage:purge-business-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, WorkOrder::count());
        $this->assertSame(0, Vehicle::count());
        $this->assertSame(0, Customer::count());
    }

    private function markSendClientAsSent(WorkOrder $workOrder, int $dclId): OutboxEntry
    {
        $entry = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->firstOrFail();

        $entry->update(['status' => 'sent', 'dcl_id' => $dclId]);

        return $entry->fresh();
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
