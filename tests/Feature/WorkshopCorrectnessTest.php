<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class WorkshopCorrectnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_form_names_produce_multiple_persisted_lines(): void
    {
        $actor = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Δοκιμή']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'TEST123']);
        $part = Part::create(['code' => 'TEST', 'name' => 'Φίλτρο', 'quantity' => 5, 'sale_price' => 12]);
        // Execute the exact serializer imported by the React form, then submit its JSON.
        $process = new Process(['node', '--input-type=module', '-e',
            "import {workOrderPayload} from './resources/js/workshop/Pages/WorkOrders/payload.js'; console.log(JSON.stringify(workOrderPayload({parts:[{source:'from_stock',part_id:".$part->id.",quantity:1,unit_price:12,unit_cost:0},{source:'from_stock',part_id:".$part->id.',quantity:1,unit_price:12,unit_cost:0}]},true)));',
        ], base_path());
        $process->mustRun();
        $payload = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(2, $payload['parts']);
        $this->assertArrayNotHasKey('part', $payload);
        $this->actingAs($actor)->post(route('workshop.work-orders.store'), $payload + [
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'Έλεγχος',
        ])->assertSessionHasNoErrors();
        $this->assertSame(2, WorkOrderPart::count());
        $this->assertEquals(24, WorkOrder::first()->total_cost);
        $this->assertEquals(3, $part->fresh()->quantity);
    }

    public function test_cancel_reopen_cancel_reconciles_stock_once_per_transition(): void
    {
        $customer = Customer::create(['full_name' => 'Δοκιμή']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'TEST123']);
        $order = WorkOrder::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'Έλεγχος', 'status' => 'new']);
        $part = Part::create(['code' => 'TEST', 'name' => 'Φίλτρο', 'quantity' => 5]);
        WorkOrderPart::create(['work_order_id' => $order->id, 'source' => 'from_stock', 'part_id' => $part->id, 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]);
        $this->assertEquals(4, $part->fresh()->quantity);
        foreach ([['cancelled', 5], ['new', 4], ['cancelled', 5], ['cancelled', 5]] as [$status, $expected]) {
            $order->update(['status' => WorkOrderStatus::from($status)]);
            $this->assertEquals($expected, $part->fresh()->quantity);
        }
        $part->update(['quantity' => 0]);
        try {
            $order->update(['status' => 'new']);
            $this->fail('Reopening must not allocate unavailable inventory.');
        } catch (ValidationException) {
            $this->assertSame(WorkOrderStatus::Cancelled, $order->fresh()->status);
            $this->assertEquals(0, $part->fresh()->quantity);
        }
    }
}
