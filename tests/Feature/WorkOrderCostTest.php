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

    public function test_parts_cost_and_total_computed_correctly(): void
    {
        $workOrder = $this->makeWorkOrder();

        $part = Part::create([
            'code' => 'P001',
            'name' => 'Test Part',
            'quantity' => 10,
            'sale_price' => 12,
        ]);

        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 4,
            'unit_price' => 12,
            'line_total' => 48,
        ]);

        $workOrder->refresh();

        $this->assertEquals(48, $workOrder->parts_cost, 'parts_cost πρέπει 4×12=48');
        $this->assertEquals(108, $workOrder->total_cost, 'total_cost πρέπει 60+48=108');
    }

    public function test_parts_cost_ignores_stale_line_total(): void
    {
        $workOrder = $this->makeWorkOrder();

        $part = Part::create([
            'code' => 'P002',
            'name' => 'Stale Part',
            'quantity' => 10,
            'sale_price' => 12,
        ]);

        // Simulate stale line_total (e.g. saved when qty was 3, but now qty=4)
        WorkOrderPart::withoutEvents(function () use ($workOrder, $part) {
            WorkOrderPart::create([
                'work_order_id' => $workOrder->id,
                'part_id' => $part->id,
                'source' => 'from_stock',
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
