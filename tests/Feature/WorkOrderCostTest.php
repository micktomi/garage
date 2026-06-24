<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderCostTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkOrder(): WorkOrder
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
            'labor_cost' => 60,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => 'new',
        ]);
    }

    public function test_single_quantity_part_computes_costs_and_line_total(): void
    {
        $workOrder = $this->makeWorkOrder();

        $part = Part::create([
            'code' => 'P001',
            'name' => 'Single Quantity Part',
            'quantity' => 10,
            'sale_price' => 20,
        ]);

        $workOrderPart = WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'quantity' => 1,
            'unit_price' => 20,
            'line_total' => 0,
        ]);

        $workOrder->refresh();
        $workOrderPart->refresh();

        $this->assertEquals(20, $workOrderPart->line_total, 'line_total πρέπει 1×20=20');
        $this->assertEquals(20, $workOrder->parts_cost, 'parts_cost πρέπει 1×20=20');
        $this->assertEquals(80, $workOrder->total_cost, 'total_cost πρέπει 60+20=80');
    }

    public function test_two_quantity_part_computes_costs_and_line_total(): void
    {
        $workOrder = $this->makeWorkOrder();

        $part = Part::create([
            'code' => 'P003',
            'name' => 'Two Quantity Part',
            'quantity' => 10,
            'sale_price' => 20,
        ]);

        $workOrderPart = WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'quantity' => 2,
            'unit_price' => 20,
            'line_total' => 0,
        ]);

        $workOrder->refresh();
        $workOrderPart->refresh();

        $this->assertEquals(40, $workOrderPart->line_total, 'line_total πρέπει 2×20=40');
        $this->assertEquals(40, $workOrder->parts_cost, 'parts_cost πρέπει 2×20=40');
        $this->assertEquals(100, $workOrder->total_cost, 'total_cost πρέπει 60+40=100');
    }

    public function test_parts_cost_ignores_stale_line_total(): void
    {
        $workOrder = $this->makeWorkOrder();

        $part = Part::create([
            'code' => 'P003',
            'name' => 'Stale Part',
            'quantity' => 10,
            'sale_price' => 12,
        ]);

        // Simulate stale line_total (e.g. saved when qty was 3, but now qty=4)
        WorkOrderPart::withoutEvents(function () use ($workOrder, $part) {
            WorkOrderPart::create([
                'work_order_id' => $workOrder->id,
                'part_id' => $part->id,
                'quantity' => 4,
                'unit_price' => 12,
                'line_total' => 36, // stale — was 3×12, now qty is 4
            ]);
        });

        $workOrder->calculatePartsCost();
        $workOrder->refresh();

        $this->assertEquals(48, $workOrder->parts_cost, 'calculatePartsCost πρέπει να χρησιμοποιεί quantity×unit_price, όχι stale line_total');
        $this->assertEquals(108, $workOrder->total_cost, 'total_cost πρέπει 60+48=108');
    }
}
