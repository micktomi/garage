<?php

namespace Tests\Feature\Listeners\Aade;

use App\Enums\ClosureDocument;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Enums\TransmissionOutcome;
use Micktomi\GarageAadeBridge\Events\DclEntrySent;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Outbox\Models\Transmission;
use Tests\TestCase;

class SyncDeferredWorkOrderCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_client_success_event_enqueues_the_deferred_update_client(): void
    {
        $workOrder = $this->completedWorkOrderMissingCompletionSync();
        [$entry, $transmission] = $this->markSendClientAsSentAndBuildTransmission($workOrder, 100000000830764);

        event(new DclEntrySent($entry->fresh(), $transmission));

        $updateEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->get();

        $this->assertCount(1, $updateEntries);
        $this->assertSame(100000000830764, $updateEntries->first()->payload['initialDclId']);
    }

    public function test_listener_is_idempotent_when_the_event_fires_more_than_once(): void
    {
        $workOrder = $this->completedWorkOrderMissingCompletionSync();
        [$entry, $transmission] = $this->markSendClientAsSentAndBuildTransmission($workOrder, 100000000830764);

        event(new DclEntrySent($entry->fresh(), $transmission));
        event(new DclEntrySent($entry->fresh(), $transmission));

        $updateEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->count();

        $this->assertSame(1, $updateEntries);
    }

    public function test_a_failure_inside_the_listener_is_logged_and_does_not_bubble(): void
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::ReadyForPickup);

        // Simulates a WorkOrder that reached Completed without a
        // closure_document — normally impossible through WorkOrder::update()
        // (guarded in WorkOrder::booted()), but exactly the kind of
        // unexpected state the listener's try/catch exists to survive: a raw
        // DB write bypasses Eloquent events/guards entirely.
        WorkOrder::query()->whereKey($workOrder->id)->update(['status' => WorkOrderStatus::Completed->value]);
        $workOrder->refresh();

        [$entry, $transmission] = $this->markSendClientAsSentAndBuildTransmission($workOrder, 100000000830764);

        Log::spy();

        event(new DclEntrySent($entry->fresh(), $transmission));

        $updateEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->count();

        $this->assertSame(0, $updateEntries);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($workOrder, $entry) {
                return $context['work_order_id'] === $workOrder->id
                    && $context['send_client_outbox_entry_id'] === $entry->id
                    && isset($context['exception_class'], $context['exception_message']);
            });
    }

    /**
     * A WorkOrder that completed while its SendClient was still unresolved:
     * the eager handleCompleted() attempt already ran and no-opped (dclId
     * was null at that moment), so it still needs completion sync.
     */
    private function completedWorkOrderMissingCompletionSync(): WorkOrder
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::ReadyForPickup);

        $workOrder->update(['status' => WorkOrderStatus::Completed, 'closure_document' => ClosureDocument::RetailReceipt]);

        return $workOrder;
    }

    /** @return array{0: OutboxEntry, 1: Transmission} */
    private function markSendClientAsSentAndBuildTransmission(WorkOrder $workOrder, int $dclId): array
    {
        OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'sent', 'dcl_id' => $dclId]);

        $entry = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->firstOrFail();

        $transmission = Transmission::create([
            'outbox_id' => $entry->id,
            'correlation_id' => $entry->correlation_id,
            'attempt_number' => 1,
            'endpoint' => 'SendClient',
            'http_method' => 'POST',
            'outcome' => TransmissionOutcome::Success->value,
            'remote_identifier' => $dclId,
            'response_status_code' => 'Success',
        ]);

        return [$entry, $transmission];
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
