<?php

namespace Tests\Feature\Console\Commands\Aade;

use App\Enums\ClosureDocument;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Tests\TestCase;

class SyncDeferredWorkOrderCompletionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sweep_enqueues_update_client_once_dcl_id_has_become_available(): void
    {
        $workOrder = $this->completedWorkOrderMissingCompletionSync();
        $this->markSendClientAsSent($workOrder, 100000000830764);

        Log::spy();

        $this->artisan('aade:sync-deferred-work-order-completions')->assertExitCode(0);

        $updateEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->count();

        $this->assertSame(1, $updateEntries);
        Log::shouldNotHaveReceived('warning');
    }

    public function test_sweep_leaves_a_recently_deferred_item_alone_without_warning(): void
    {
        $workOrder = $this->completedWorkOrderMissingCompletionSync();
        // send_client entry stays pending (never marked sent) — dclId still
        // unresolved, checked_out_at was just set (< 10 minutes ago).

        Log::spy();

        $this->artisan('aade:sync-deferred-work-order-completions')->assertExitCode(0);

        $this->assertSame(0, $this->updateClientCount($workOrder));
        Log::shouldNotHaveReceived('warning');
    }

    public function test_sweep_warns_once_a_deferred_item_has_waited_past_the_threshold(): void
    {
        $workOrder = $this->completedWorkOrderMissingCompletionSync();
        $this->ageCheckedOutAt($workOrder, 15);
        // send_client entry stays pending — dclId still unresolved.

        Log::spy();

        $this->artisan('aade:sync-deferred-work-order-completions')->assertExitCode(0);

        $this->assertSame(0, $this->updateClientCount($workOrder));

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($workOrder) {
                return $context['work_order_id'] === $workOrder->id
                    && $context['waiting_seconds'] > 600
                    && $context['deferred_state'] === 'send_client_pending'
                    && array_key_exists('send_client_outbox_entry_id', $context)
                    && $context['send_client_status'] === 'pending'
                    && $context['send_client_failure_kind'] === null
                    && ! str_contains(json_encode($context), 'payload');
            });
    }

    public function test_sweep_after_the_listener_already_resolved_it_does_not_duplicate_update_client(): void
    {
        $workOrder = $this->completedWorkOrderMissingCompletionSync();
        $this->markSendClientAsSent($workOrder, 100000000830764);

        // Simulates the event listener already having handled this
        // successfully before the sweep gets a chance to run.
        app(\App\Services\Aade\WorkOrderAadeSync::class)->handleCompleted($workOrder);
        $this->assertSame(1, $this->updateClientCount($workOrder));

        $this->artisan('aade:sync-deferred-work-order-completions')->assertExitCode(0);

        $this->assertSame(1, $this->updateClientCount($workOrder));
    }

    public function test_permanently_failed_send_client_does_not_spam_the_outbox_across_repeated_sweeps(): void
    {
        $workOrder = $this->completedWorkOrderMissingCompletionSync();
        $this->markSendClientAsPermanentlyFailed($workOrder);
        $this->ageCheckedOutAt($workOrder, 20);

        Log::spy();

        $this->artisan('aade:sync-deferred-work-order-completions')->assertExitCode(0);
        $this->artisan('aade:sync-deferred-work-order-completions')->assertExitCode(0);

        $this->assertSame(0, $this->updateClientCount($workOrder));

        // Only the original send_client row for this entity — repeated
        // sweeps never create new outbox entries for a permanent failure.
        $this->assertSame(1, OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->count());

        Log::shouldHaveReceived('warning')->twice();
    }

    private function updateClientCount(WorkOrder $workOrder): int
    {
        return OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->count();
    }

    private function ageCheckedOutAt(WorkOrder $workOrder, int $minutesAgo): void
    {
        $workOrder->forceFill(['checked_out_at' => now()->subMinutes($minutesAgo)])->saveQuietly();
    }

    private function markSendClientAsSent(WorkOrder $workOrder, int $dclId): void
    {
        OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'sent', 'dcl_id' => $dclId]);
    }

    private function markSendClientAsPermanentlyFailed(WorkOrder $workOrder): void
    {
        OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'failed', 'failure_kind' => 'permanent', 'dcl_id' => null]);
    }

    private function completedWorkOrderMissingCompletionSync(): WorkOrder
    {
        $workOrder = $this->createWorkOrder(WorkOrderStatus::ReadyForPickup);

        $workOrder->update(['status' => WorkOrderStatus::Completed, 'closure_document' => ClosureDocument::RetailReceipt]);

        return $workOrder;
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
