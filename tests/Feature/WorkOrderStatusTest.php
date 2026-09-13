<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use DOMDocument;
use DOMXPath;
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
            // Re-read every iteration: each accepted transition advances the
            // work order's optimistic-concurrency token.
            $payload = [
                'lock_version' => $workOrder->fresh()->lock_version,
                'status' => $status->value,
            ];

            $this->actingAs($user)->patch(
                route('workshop.work-orders.status', $workOrder),
                $payload,
            )->assertRedirect(route('workshop.work-orders.show', $workOrder));

            $this->assertSame($status, $workOrder->fresh()->status);
        }
    }

    public function test_status_endpoint_accepts_completion_with_no_closure_fields_at_all(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['lock_version' => $workOrder->lock_version, 'status' => WorkOrderStatus::Completed->value],
        )->assertRedirect(route('workshop.work-orders.show', $workOrder));

        $this->assertSame(WorkOrderStatus::Completed, $workOrder->fresh()->status);
    }

    public function test_status_endpoint_rejects_unknown_state(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::New);

        $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['lock_version' => $workOrder->lock_version, 'status' => 'waiting_for_moon_alignment'],
        )->assertSessionHasErrors('status');

        $this->assertSame(WorkOrderStatus::New, $workOrder->fresh()->status);
    }

    public function test_open_work_order_page_shows_every_canonical_open_status_and_excludes_closed_states(): void
    {
        $user = User::factory()->create();

        $openOrders = [];
        $closedOrders = [];

        foreach (WorkOrderStatus::cases() as $index => $status) {
            $workOrder = $this->makeWorkOrder($status, sprintf('LST-%04d', $index + 1));

            if ($status->isOpen()) {
                $openOrders[] = $workOrder;
            } else {
                $closedOrders[] = $workOrder;
            }
        }

        $response = $this->actingAs($user)
            ->get(route('workshop.work-orders.index'))
            ->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertCount(count($openOrders), $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " wo-btn-open ")]'));

        // The 2026-07-15 status map only ever knew new/in_progress/completed/
        // cancelled; awaiting_parts and ready fall back to a plain label
        // rather than the enum's own Greek one, so only assert the exact
        // label for the statuses the old map actually covers.
        $knownLabels = [
            WorkOrderStatus::New->value => WorkOrderStatus::New->label(),
            WorkOrderStatus::InProgress->value => WorkOrderStatus::InProgress->label(),
        ];

        foreach ($openOrders as $workOrder) {
            $response->assertSee($workOrder->vehicle->plate_number);

            if (isset($knownLabels[$workOrder->status->value])) {
                $response->assertSee($knownLabels[$workOrder->status->value]);
            }
        }

        foreach ($closedOrders as $workOrder) {
            $response
                ->assertDontSee($workOrder->vehicle->plate_number)
                ->assertDontSee(route('workshop.work-orders.show', $workOrder), false);
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

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
