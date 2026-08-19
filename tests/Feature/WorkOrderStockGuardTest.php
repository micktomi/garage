<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Stock is a domain invariant, not a form rule: parts.quantity must never go
 * negative, whichever UI consumed it. Filament enforces it in its own form;
 * /workshop did not, and neither UI's per-row check can see two rows of the
 * same part or a competing consumption.
 */
class WorkOrderStockGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_rejects_a_part_row_that_exceeds_available_stock(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->makeVehicle('ΑΠΘ-1000');
        $part = $this->makePart('ΑΠΘ-1', quantity: 1);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αλλαγή τακακιών',
            'parts' => [[
                'source' => 'from_stock',
                'part_id' => $part->id,
                'quantity' => '10',
                'unit_price' => '5',
            ]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, WorkOrder::count());
        $this->assertSame(1.0, (float) $part->fresh()->quantity, 'Stock must be untouched when the order is rejected.');
    }

    public function test_workshop_accepts_a_part_row_that_exactly_matches_available_stock(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->makeVehicle('ΑΠΘ-2000');
        $part = $this->makePart('ΑΠΘ-2', quantity: 3);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αλλαγή φίλτρων',
            'parts' => [[
                'source' => 'from_stock',
                'part_id' => $part->id,
                'quantity' => '3',
                'unit_price' => '5',
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, WorkOrder::count());
        $this->assertSame(0.0, (float) $part->fresh()->quantity);
    }

    public function test_workshop_rejects_two_rows_of_the_same_part_that_together_exceed_stock(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->makeVehicle('ΑΠΘ-3000');
        $part = $this->makePart('ΑΠΘ-3', quantity: 2);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Δύο γραμμές ίδιου ανταλλακτικού',
            'parts' => [
                ['source' => 'from_stock', 'part_id' => $part->id, 'quantity' => '2', 'unit_price' => '5'],
                ['source' => 'from_stock', 'part_id' => $part->id, 'quantity' => '1', 'unit_price' => '5'],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, WorkOrder::count());
        $this->assertSame(2.0, (float) $part->fresh()->quantity);
    }

    public function test_the_model_layer_refuses_to_drive_stock_negative(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΠΘ-4000');
        $part = $this->makePart('ΑΠΘ-4', quantity: 2);

        try {
            WorkOrderPart::create([
                'work_order_id' => $workOrder->id,
                'part_id' => $part->id,
                'source' => 'from_stock',
                'quantity' => 5,
                'unit_price' => 10,
                'line_total' => 50,
            ]);
            $this->fail('Consuming more than the available stock must be refused.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame(2.0, (float) $part->fresh()->quantity);
    }

    public function test_a_competing_consumption_cannot_drive_stock_negative(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΠΘ-5000');
        $part = $this->makePart('ΑΠΘ-5', quantity: 1);

        // First consumption wins the last unit. The second one is exactly what
        // a second user submitting at the same moment produces: its own
        // validation saw stock=1 too, so only the write itself can stop it.
        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);

        $this->assertSame(0.0, (float) $part->fresh()->quantity);

        $this->expectException(ValidationException::class);

        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);
    }

    public function test_raising_the_quantity_of_an_existing_row_cannot_drive_stock_negative(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΠΘ-6000');
        $part = $this->makePart('ΑΠΘ-6', quantity: 4);

        $row = WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 2,
            'unit_price' => 10,
            'line_total' => 20,
        ]);

        $this->assertSame(2.0, (float) $part->fresh()->quantity);

        try {
            $row->update(['quantity' => 9]);
            $this->fail('Raising a row beyond the available stock must be refused.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertGreaterThanOrEqual(0.0, (float) $part->fresh()->quantity);
        $this->assertSame(2.0, (float) $part->fresh()->quantity);
    }

    public function test_lowering_the_quantity_of_an_existing_row_returns_stock(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΠΘ-7000');
        $part = $this->makePart('ΑΠΘ-7', quantity: 4);

        $row = WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 3,
            'unit_price' => 10,
            'line_total' => 30,
        ]);

        $row->update(['quantity' => 1]);

        $this->assertSame(3.0, (float) $part->fresh()->quantity);
    }

    private function makePart(string $code, float $quantity): Part
    {
        return Part::create([
            'code' => $code,
            'name' => 'Ανταλλακτικό '.$code,
            'quantity' => $quantity,
            'purchase_price' => 3,
            'sale_price' => 5,
        ]);
    }

    private function makeVehicle(string $plate): Vehicle
    {
        $customer = Customer::create(['full_name' => 'Πελάτης '.$plate]);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
        ]);
    }

    private function makeWorkOrder(string $plate): WorkOrder
    {
        $vehicle = $this->makeVehicle($plate);

        return WorkOrder::create([
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => 'new',
        ]);
    }
}
