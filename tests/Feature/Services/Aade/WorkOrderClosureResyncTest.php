<?php

namespace Tests\Feature\Services\Aade;

use App\Enums\ClosureDocument;
use App\Enums\NonIssueReason;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Tests\TestCase;

/**
 * closure_document / non_issue_reason are what ΑΑΔΕ is told was issued when
 * the vehicle left. If they can be corrected after completion — and Filament
 * lets them be — the correction has to reach ΑΑΔΕ. Anything else means the
 * garage's books and the Digital Client List silently disagree about which
 * παραστατικό was issued.
 */
class WorkOrderClosureResyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_correcting_the_closure_document_of_a_completed_work_order_enqueues_a_corrected_update_client(): void
    {
        $workOrder = $this->completedWorkOrder(ClosureDocument::RetailReceipt);

        $this->assertCount(1, $this->updateEntriesFor($workOrder));

        $workOrder->update(['closure_document' => ClosureDocument::Invoice]);

        $entries = $this->updateEntriesFor($workOrder);

        $this->assertCount(2, $entries, 'Correcting the παραστατικό must produce a second UpdateClient.');
        $this->assertSame(1, $entries->first()->payload['invoiceKind']);
        $this->assertSame(2, $entries->last()->payload['invoiceKind']);
    }

    public function test_correcting_the_non_issue_reason_of_a_completed_work_order_enqueues_a_corrected_update_client(): void
    {
        $workOrder = $this->completedWorkOrder(ClosureDocument::None, NonIssueReason::Warranty);

        $workOrder->update(['non_issue_reason' => NonIssueReason::FreeService]);

        $entries = $this->updateEntriesFor($workOrder);

        $this->assertCount(2, $entries);
        $this->assertSame(3, $entries->first()->payload['reasonNonIssueType']);
        $this->assertSame(1, $entries->last()->payload['reasonNonIssueType']);
    }

    public function test_an_unrelated_edit_on_a_completed_work_order_enqueues_nothing_new(): void
    {
        $workOrder = $this->completedWorkOrder(ClosureDocument::RetailReceipt);

        $workOrder->update(['work_performed' => 'Συμπληρώθηκε η περιγραφή εργασιών.']);
        $workOrder->update(['diagnosis' => 'Φθορά αναλωσίμων.']);

        $this->assertCount(1, $this->updateEntriesFor($workOrder));
    }

    public function test_the_deferred_sweep_catches_a_closure_correction_that_never_reached_the_outbox(): void
    {
        $workOrder = $this->completedWorkOrder(ClosureDocument::RetailReceipt);

        // A raw query-builder update bypasses Eloquent events entirely — the
        // same end state you get when the enqueue inside the model hook threw.
        // The safety-net sweep is the only thing left that can notice.
        WorkOrder::query()
            ->whereKey($workOrder->id)
            ->update(['closure_document' => ClosureDocument::Invoice->value]);

        $this->assertCount(1, $this->updateEntriesFor($workOrder));

        $this->artisan('aade:sync-deferred-work-order-completions')->assertSuccessful();

        $entries = $this->updateEntriesFor($workOrder);

        $this->assertCount(2, $entries, 'The sweep must re-enqueue a corrected UpdateClient.');
        $this->assertSame(2, $entries->last()->payload['invoiceKind']);
    }

    public function test_the_deferred_sweep_stays_quiet_once_the_correction_is_enqueued(): void
    {
        $workOrder = $this->completedWorkOrder(ClosureDocument::RetailReceipt);
        $workOrder->update(['closure_document' => ClosureDocument::Invoice]);

        $this->artisan('aade:sync-deferred-work-order-completions')->assertSuccessful();
        $this->artisan('aade:sync-deferred-work-order-completions')->assertSuccessful();

        $this->assertCount(2, $this->updateEntriesFor($workOrder));
    }

    /** @return Collection<int, OutboxEntry> */
    private function updateEntriesFor(WorkOrder $workOrder): Collection
    {
        return OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->orderBy('id')
            ->get();
    }

    private function completedWorkOrder(ClosureDocument $document, ?NonIssueReason $reason = null): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Πελάτης ΚΛΣ']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΚΛΣ'.random_int(1000, 9999),
        ]);

        $workOrder = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => WorkOrderStatus::ReadyForPickup,
        ]);

        OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'sent', 'dcl_id' => 100000000830764]);

        $workOrder->update([
            'status' => WorkOrderStatus::Completed,
            'closure_document' => $document,
            'non_issue_reason' => $reason,
        ]);

        return $workOrder;
    }
}
