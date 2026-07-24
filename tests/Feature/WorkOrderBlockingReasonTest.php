<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderBlockingReasonTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkOrder(string $status = 'in_progress', ?string $blockingReason = null): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Test Customer']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΑΑΑ-0001',
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        return WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Test',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => $status,
            'blocking_reason' => $blockingReason,
        ]);
    }

    public function test_blocking_reason_can_be_set_while_in_progress(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder('in_progress');

        $response = $this->actingAs($user)->patch(
            route('workshop.work-orders.blocking-reason', $workOrder),
            ['blocking_reason' => 'waiting_parts']
        );

        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals('waiting_parts', $workOrder->fresh()->blocking_reason);
    }

    public function test_blocking_reason_rejects_value_outside_allowed_list(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder('in_progress');

        $response = $this->actingAs($user)->patch(
            route('workshop.work-orders.blocking-reason', $workOrder),
            ['blocking_reason' => 'waiting_for_moon_alignment']
        );

        $response->assertSessionHasErrors('blocking_reason');
        $this->assertNull($workOrder->fresh()->blocking_reason);
    }

    public function test_blocking_reason_rejected_when_status_is_not_in_progress(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder('new');

        $response = $this->actingAs($user)->patch(
            route('workshop.work-orders.blocking-reason', $workOrder),
            ['blocking_reason' => 'waiting_parts']
        );

        $response->assertSessionHasErrors('blocking_reason');
        $this->assertNull($workOrder->fresh()->blocking_reason);
    }

    public function test_blocking_reason_can_be_cleared_while_in_progress(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder('in_progress', 'waiting_customer_approval');

        $response = $this->actingAs($user)->patch(
            route('workshop.work-orders.blocking-reason', $workOrder),
            ['blocking_reason' => '']
        );

        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertNull($workOrder->fresh()->blocking_reason);
    }

    public function test_blocking_reason_auto_clears_when_status_changes_via_status_endpoint(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder('in_progress', 'waiting_parts');

        $response = $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['status' => 'completed']
        );

        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $workOrder->refresh();
        $this->assertEquals('completed', $workOrder->status);
        $this->assertNull($workOrder->blocking_reason);
    }

    public function test_blocking_reason_auto_clears_on_direct_model_update(): void
    {
        // Guards the invariant at the model level, independent of any
        // particular controller entry point (e.g. Filament, tinker, etc).
        $workOrder = $this->makeWorkOrder('in_progress', 'other');

        $workOrder->update(['status' => 'cancelled']);

        $this->assertNull($workOrder->fresh()->blocking_reason);
    }

    public function test_blocking_reason_survives_unrelated_update_while_still_in_progress(): void
    {
        $workOrder = $this->makeWorkOrder('in_progress', 'waiting_parts');

        $workOrder->update(['diagnosis' => 'Χρειάζεται νέο φίλτρο']);

        $this->assertEquals('waiting_parts', $workOrder->fresh()->blocking_reason);
    }

    public function test_blocking_reason_badge_renders_on_show_index_and_dashboard(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder('in_progress', 'waiting_customer_approval');

        $this->actingAs($user)->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Αναμονή έγκρισης πελάτη')
            ->assertSee('Αιτία καθυστέρησης');

        $this->actingAs($user)->get(route('workshop.work-orders.index'))
            ->assertOk()
            ->assertSee('Αναμονή έγκρισης πελάτη');

        $this->actingAs($user)->get(route('workshop.dashboard'))
            ->assertOk()
            ->assertSee('Αναμονή έγκρισης πελάτη');
    }

    public function test_blocking_reason_section_hidden_when_not_in_progress(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder('completed');

        $this->actingAs($user)->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk()
            ->assertDontSee('Αιτία καθυστέρησης');
    }
}
