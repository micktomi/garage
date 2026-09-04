<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Two people, one work order. The counter closes it out; a tab opened before
 * that still shows it as open and its owner clicks a status button too.
 * Before this, the second click won silently — whichever request happened to
 * commit last overwrote the other's status with no one told anything had
 * happened.
 *
 * The guarantee asserted here is narrow on purpose: a stale representation
 * must not be able to change the record *silently*. Being told, and retrying
 * against the current state, is a perfectly good outcome.
 */
class WorkOrderStaleEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_update_advances_the_version(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::New);
        $first = $workOrder->lock_version;

        $workOrder->update(['diagnosis' => 'Μπουζί']);

        $this->assertSame($first + 1, $workOrder->fresh()->lock_version);
    }

    /**
     * The old assertNotStale() compared the submitted token against an
     * in-memory value, then a later, separate save() actually wrote — a real
     * gap for two requests whose check-then-write windows overlap, which the
     * sequential HTTP tests below cannot exercise (an HTTP call in a test is
     * always fully finished before the next one starts). This reproduces the
     * overlap directly: $workOrder never re-reads after it is loaded, so
     * calling updateWithExpectedVersion() with the version it still believes
     * is current is exactly what a second, truly concurrent request would do
     * — the row underneath it has already moved.
     */
    public function test_a_write_racing_underneath_an_already_loaded_record_is_rejected_not_silently_overwritten(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);
        $staleVersion = $workOrder->lock_version;

        // The "other terminal": lands between this test loading $workOrder
        // and calling updateWithExpectedVersion() below, with nothing in
        // between re-reading $workOrder — the same shape a genuine race has.
        DB::table('work_orders')->where('id', $workOrder->id)->update([
            'status' => WorkOrderStatus::Completed->value,
            'lock_version' => $staleVersion + 1,
        ]);

        try {
            $workOrder->updateWithExpectedVersion($staleVersion, [
                'status' => WorkOrderStatus::Cancelled,
            ]);
            $this->fail('Expected a ValidationException for the stale lock_version.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lock_version', $exception->errors());
        }

        $fresh = WorkOrder::find($workOrder->id);
        $this->assertSame(WorkOrderStatus::Completed, $fresh->status, 'The losing write must not overwrite the winning one.');
        $this->assertSame($staleVersion + 1, $fresh->lock_version, 'A rejected write must not move the version either.');
    }

    /**
     * A second query between the check and the write is exactly the gap
     * updateWithExpectedVersion() exists to close — so the WHERE lock_version
     * condition and the SET lock_version = lock_version+1 have to be the same
     * UPDATE statement, not a read followed by a write.
     */
    public function test_the_stale_check_and_the_version_bump_are_a_single_update_statement(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress);
        $version = $workOrder->lock_version;

        DB::enableQueryLog();
        $workOrder->updateWithExpectedVersion($version, ['diagnosis' => 'Μπαταρία']);
        $log = DB::getQueryLog();
        DB::flushQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $log, 'Expected exactly one query: the guarded UPDATE itself.');
        $this->assertStringStartsWith('update', strtolower(trim($log[0]['query'])));
        $this->assertStringContainsString('lock_version', $log[0]['query']);
        $this->assertSame($version + 1, $workOrder->fresh()->lock_version);
    }

    public function test_a_stale_page_cannot_silently_reopen_a_completed_order(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);
        $staleVersion = $workOrder->lock_version;

        $workOrder->update(['status' => WorkOrderStatus::Completed]);

        $this->actingAs($user)->patch(route('workshop.work-orders.status', $workOrder), [
            'lock_version' => $staleVersion,
            'status' => WorkOrderStatus::InProgress->value,
        ])->assertSessionHasErrors('lock_version');

        $this->assertSame(WorkOrderStatus::Completed, $workOrder->fresh()->status);
    }

    public function test_a_submission_carrying_no_version_at_all_is_refused(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        $this->actingAs($user)->patch(route('workshop.work-orders.status', $workOrder), [
            'status' => WorkOrderStatus::Completed->value,
        ])->assertSessionHasErrors('lock_version');

        $this->assertSame(WorkOrderStatus::ReadyForPickup, $workOrder->fresh()->status);
    }

    public function test_a_current_submission_still_goes_through(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        $this->actingAs($user)->patch(route('workshop.work-orders.status', $workOrder), [
            'lock_version' => $workOrder->lock_version,
            'status' => WorkOrderStatus::Completed->value,
        ])->assertRedirect(route('workshop.work-orders.show', $workOrder));

        $this->assertSame(WorkOrderStatus::Completed, $workOrder->fresh()->status);
    }

    public function test_every_status_form_carries_the_current_version(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress);
        $workOrder->update(['diagnosis' => 'Έλεγχος']);
        $workOrder = $workOrder->fresh();

        $response = $this->actingAs($user)
            ->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk();

        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $inputs = (new DOMXPath($document))->query('//form[contains(concat(" ", normalize-space(@class), " "), " wos-status-form ")]//input[@name="lock_version"]');

        $this->assertCount(5, $inputs, 'Every status form must submit the version it was rendered from.');

        foreach ($inputs as $input) {
            $this->assertSame((string) $workOrder->lock_version, $input->getAttribute('value'));
        }
    }

    /**
     * parts_cost is recomputed with saveQuietly() every time a line changes.
     * If that bumped the version, adding a part would invalidate a status
     * panel nobody had touched — a false conflict, and the fastest way to
     * teach people to ignore the warning.
     */
    public function test_recalculating_costs_does_not_invalidate_an_open_page(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress);
        $version = $workOrder->lock_version;

        $part = Part::create(['code' => 'STL-1', 'name' => 'Φίλτρο', 'quantity' => 5, 'sale_price' => 10]);
        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);

        $this->assertSame(10.0, (float) $workOrder->fresh()->parts_cost);
        $this->assertSame($version, $workOrder->fresh()->lock_version);
    }

    public function test_filament_refuses_to_save_a_stale_diagnosis(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        $page = Livewire::actingAs($user)->test(EditWorkOrder::class, ['record' => $workOrder->id]);

        // Someone else edits it while this edit form is open.
        $workOrder->update(['diagnosis' => 'Μπουζί']);

        $page->fillForm([
            'status' => WorkOrderStatus::ReadyForPickup->value,
            'diagnosis' => 'Τακάκια',
        ])->call('save');

        $this->assertSame(
            'Μπουζί',
            $workOrder->fresh()->diagnosis,
            'Filament let a form opened before the change overwrite it.',
        );
    }

    public function test_filament_saves_normally_when_nothing_changed_underneath(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        Livewire::actingAs($user)
            ->test(EditWorkOrder::class, ['record' => $workOrder->id])
            ->fillForm([
                'status' => WorkOrderStatus::ReadyForPickup->value,
                'diagnosis' => 'Τακάκια',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Τακάκια', $workOrder->fresh()->diagnosis);
    }

    private function makeWorkOrder(WorkOrderStatus $status, string $plate = 'ΣΤΛ-0001'): WorkOrder
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
            'status' => $status,
        ]);
    }
}
