<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_status_is_cast_to_the_domain_enum(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::AwaitingParts);

        $this->assertSame(WorkOrderStatus::AwaitingParts, $workOrder->status);
        $this->assertSame('Αναμονή ανταλλακτικού', $workOrder->status->label());
        $this->assertTrue($workOrder->status->isOpen());
        $this->assertFalse(WorkOrderStatus::Completed->isOpen());
    }

    public function test_status_endpoint_accepts_all_new_workflow_states(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::New);

        foreach ([
            WorkOrderStatus::InProgress,
            WorkOrderStatus::AwaitingParts,
            WorkOrderStatus::ReadyForPickup,
            WorkOrderStatus::Completed,
        ] as $status) {
            $this->actingAs($user)->patch(
                route('workshop.work-orders.status', $workOrder),
                ['status' => $status->value],
            )->assertRedirect(route('workshop.work-orders.show', $workOrder));

            $this->assertSame($status, $workOrder->fresh()->status);
        }
    }

    public function test_status_endpoint_rejects_unknown_state(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::New);

        $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['status' => 'waiting_for_moon_alignment'],
        )->assertSessionHasErrors('status');

        $this->assertSame(WorkOrderStatus::New, $workOrder->fresh()->status);
    }

    public function test_open_work_order_page_uses_enum_open_states(): void
    {
        $user = User::factory()->create();

        $open = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup, 'ΑΝΟ-1000');
        $completed = $this->makeWorkOrder(WorkOrderStatus::Completed, 'ΚΛΕ-2000');

        $this->actingAs($user)
            ->get(route('workshop.work-orders.index'))
            ->assertOk()
            ->assertSee($open->vehicle->plate_number)
            ->assertDontSee($completed->vehicle->plate_number);
    }

    public function test_existing_work_order_detail_page_opens_for_every_supported_status(): void
    {
        $user = User::factory()->create();

        foreach (WorkOrderStatus::cases() as $index => $status) {
            $workOrder = $this->makeWorkOrder($status, sprintf('STA-%04d', $index + 1));

            $response = $this->actingAs($user)
                ->get(route('workshop.work-orders.show', $workOrder));

            $response
                ->assertOk()
                ->assertSee($status->label())
                ->assertSee(route('work-orders.print', $workOrder), false)
                ->assertSee(route('workshop.work-orders.status', $workOrder), false)
                ->assertDontSee('wo-badge--blocking')
                ->assertDontSee('wos-status-btn--blocking');

            $this->assertSame(
                2,
                substr_count($response->getContent(), '<span class="ws-status-badge '),
                "Expected exactly the toolbar and detail badges for status [{$status->value}].",
            );
        }
    }

    public function test_blocking_reason_flag_and_route_are_removed(): void
    {
        $this->assertFalse(Schema::hasColumn('work_orders', 'blocking_reason'));
        $this->assertFalse(Route::has('workshop.work-orders.blocking-reason'));
    }

    private function makeWorkOrder(WorkOrderStatus $status, string $plate = 'ΑΑΑ-0001'): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Test Customer '.$plate]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Toyota',
            'model' => 'Yaris',
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
