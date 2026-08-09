<?php

namespace Tests\Feature\Services\Aade;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Tests\TestCase;

class WorkOrderAadeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_open_work_order_enqueues_exactly_one_send_client_entry(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::New);

        $entries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->get();

        $this->assertCount(1, $entries);
        $this->assertSame('send_client', $entries->first()->operation->value);
        $this->assertSame('pending', $entries->first()->status->value);
        $this->assertSame($workOrder->vehicle->plate_number, $entries->first()->payload['useCase']['vehicleRegistrationNumber']);
    }

    public function test_a_work_order_seeded_as_already_closed_does_not_enqueue_anything(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::Completed);

        $this->assertFalse($workOrder->in_shop);

        $entries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->count();

        $this->assertSame(0, $entries);
    }

    public function test_a_work_order_seeded_as_already_cancelled_does_not_enqueue_anything(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::Cancelled);

        $this->assertFalse($workOrder->in_shop);

        $entries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->count();

        $this->assertSame(0, $entries);
    }

    public function test_changing_status_on_an_existing_work_order_does_not_enqueue_a_second_send_client(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::New);
        $workOrder->update(['status' => WorkOrderStatus::InProgress]);

        $entries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->count();

        $this->assertSame(1, $entries);
    }

    private function createWorkOrder(WorkOrderStatus $status): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Test Customer']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΔΟΚ'.random_int(1000, 9999),
        ]);

        return WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Test service',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => $status,
        ]);
    }
}
