<?php

namespace Tests\Feature\Services\Aade;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages\ListWorkOrders;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Filament\Facades\Filament;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Tests\TestCase;

/**
 * The ΑΑΔΕ Digital Client List lifecycle invariant:
 * SendClient opens an entry, and only UpdateClient (completion) or
 * CancelClient (cancellation/removal) closes it. A work order that opened an
 * entry and then disappears from the garage's books — cancelled or deleted —
 * must close it, otherwise the vehicle stays "in the shop" at ΑΑΔΕ forever.
 */
class WorkOrderCancelSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_a_work_order_with_a_resolved_dcl_id_enqueues_a_cancel_client_entry(): void
    {
        $workOrder = $this->createWorkOrder();
        $this->markSendClientAsSent($workOrder, 100000000830764);

        $workOrder->update(['status' => WorkOrderStatus::Cancelled]);

        $cancelEntries = $this->cancelEntriesFor($workOrder);

        $this->assertCount(1, $cancelEntries);
        $this->assertSame(100000000830764, $cancelEntries->first()->payload['dclId']);
        $this->assertSame('pending', $cancelEntries->first()->status->value);
    }

    public function test_cancelling_a_work_order_without_a_resolved_dcl_id_enqueues_nothing(): void
    {
        $workOrder = $this->createWorkOrder();
        // The check-in SendClient is still pending — ΑΑΔΕ never saw this
        // vehicle, so there is nothing to cancel.

        $workOrder->update(['status' => WorkOrderStatus::Cancelled]);

        $this->assertCount(0, $this->cancelEntriesFor($workOrder));
    }

    public function test_cancelling_twice_does_not_enqueue_a_second_cancel_client_entry(): void
    {
        $workOrder = $this->createWorkOrder();
        $this->markSendClientAsSent($workOrder, 100000000830764);

        $workOrder->update(['status' => WorkOrderStatus::Cancelled]);
        $workOrder->update(['status' => WorkOrderStatus::New]);
        $workOrder->update(['status' => WorkOrderStatus::Cancelled]);

        $this->assertCount(1, $this->cancelEntriesFor($workOrder));
    }

    public function test_deleting_a_work_order_with_a_resolved_dcl_id_enqueues_a_cancel_client_entry(): void
    {
        $workOrder = $this->createWorkOrder();
        $this->markSendClientAsSent($workOrder, 100000000830764);
        $workOrderId = $workOrder->id;

        $workOrder->delete();

        $cancelEntries = OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrderId)
            ->where('operation', 'cancel_client')
            ->get();

        $this->assertCount(1, $cancelEntries);
        $this->assertSame(100000000830764, $cancelEntries->first()->payload['dclId']);
    }

    public function test_filament_bulk_delete_enqueues_a_cancel_client_entry_for_every_deleted_work_order(): void
    {
        $admin = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $first = $this->createWorkOrder('ΑΚΥ-1000');
        $second = $this->createWorkOrder('ΑΚΥ-2000');
        $this->markSendClientAsSent($first, 100000000830764);
        $this->markSendClientAsSent($second, 100000000830765);

        Livewire::actingAs($admin)
            ->test(ListWorkOrders::class)
            ->callTableBulkAction(DeleteBulkAction::class, [$first->getKey(), $second->getKey()]);

        $this->assertSame(0, WorkOrder::count());
        $this->assertCount(1, $this->cancelEntriesFor($first));
        $this->assertCount(1, $this->cancelEntriesFor($second));
        $this->assertSame(100000000830765, $this->cancelEntriesFor($second)->first()->payload['dclId']);
    }

    public function test_a_broken_aade_outbox_does_not_roll_back_the_cancellation(): void
    {
        $workOrder = $this->createWorkOrder();
        $this->markSendClientAsSent($workOrder, 100000000830764);

        $part = Part::create(['code' => 'CNC-1', 'name' => 'Τακάκια', 'quantity' => 10, 'sale_price' => 20]);
        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 4,
            'unit_price' => 20,
            'line_total' => 80,
        ]);
        $this->assertSame(6.0, (float) $part->fresh()->quantity);

        // The whole ΑΑΔΕ outbox layer is unavailable. Cancelling a work order
        // is a decision the user already made; the ΑΑΔΕ side failing must not
        // undo it, and must not skip the stock restoration either.
        Schema::drop('aade_dcl_outbox');
        Log::spy();

        $workOrder->update(['status' => WorkOrderStatus::Cancelled]);

        $this->assertSame(WorkOrderStatus::Cancelled, $workOrder->fresh()->status);
        $this->assertSame(10.0, (float) $part->fresh()->quantity);
        Log::shouldHaveReceived('error')->once();
    }

    /** @return Collection<int, OutboxEntry> */
    private function cancelEntriesFor(WorkOrder $workOrder): Collection
    {
        return OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'cancel_client')
            ->get();
    }

    private function markSendClientAsSent(WorkOrder $workOrder, int $dclId): void
    {
        OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'sent', 'dcl_id' => $dclId]);
    }

    private function createWorkOrder(string $plate = 'ΑΚΥ-0001'): WorkOrder
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
