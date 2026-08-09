<?php

namespace Tests\Feature\Services\Aade;

use App\Enums\ClosureDocument;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
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

    public function test_completing_an_open_work_order_with_a_resolved_dcl_id_enqueues_exactly_one_update_client_entry(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::ReadyForPickup);
        $this->markSendClientAsSent($workOrder, 100000000830764);

        $workOrder->update(['status' => WorkOrderStatus::Completed, 'closure_document' => ClosureDocument::RetailReceipt]);

        $updateEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->get();

        $this->assertCount(1, $updateEntries);
        $this->assertSame('pending', $updateEntries->first()->status->value);
        $this->assertSame(100000000830764, $updateEntries->first()->payload['initialDclId']);
    }

    public function test_resaving_an_already_completed_work_order_does_not_enqueue_a_second_update_client_entry(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::ReadyForPickup);
        $this->markSendClientAsSent($workOrder, 100000000830764);

        $workOrder->update(['status' => WorkOrderStatus::Completed, 'closure_document' => ClosureDocument::RetailReceipt]);
        $workOrder->update(['work_performed' => 'Ολοκληρώθηκε ο έλεγχος.']);
        $workOrder->update(['status' => WorkOrderStatus::Completed]);

        $updateEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->count();

        $this->assertSame(1, $updateEntries);
    }

    public function test_completing_a_work_order_without_a_resolved_dcl_id_enqueues_nothing_and_logs_a_warning(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::ReadyForPickup);
        // No markSendClientAsSent(): the checkpoint-4 SendClient entry created
        // at checkin is still pending (never actually sent), so resolveDclId
        // must come back null.

        Log::spy();

        $workOrder->update(['status' => WorkOrderStatus::Completed, 'closure_document' => ClosureDocument::RetailReceipt]);

        $this->assertSame(WorkOrderStatus::Completed, $workOrder->fresh()->status);

        $updateEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->count();

        $this->assertSame(0, $updateEntries);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => $context['work_order_id'] === $workOrder->id);
    }

    public function test_closure_validation_still_applies_when_a_dcl_id_is_resolved(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::ReadyForPickup);
        $this->markSendClientAsSent($workOrder, 100000000830764);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $workOrder->update(['status' => WorkOrderStatus::Completed]);
    }

    private function markSendClientAsSent(WorkOrder $workOrder, int $dclId): void
    {
        OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'sent', 'dcl_id' => $dclId]);
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
