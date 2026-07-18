<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerAndVehicle(string $plate = 'ΑΒΓ-1234'): array
    {
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        return [$customer, $vehicle];
    }

    public function test_create_form_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.work-orders.create'));

        $response->assertOk();
        $response->assertSee('Νέα Εντολή Εργασίας');
    }

    public function test_work_order_can_be_created_with_vehicle_belonging_to_customer(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle();

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αλλαγή λαδιών',
            'labor_cost' => 40,
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals($customer->id, $workOrder->customer_id);
        $this->assertEquals($vehicle->id, $workOrder->vehicle_id);
    }

    public function test_work_order_rejects_vehicle_belonging_to_different_customer(): void
    {
        $user = User::factory()->create();
        $customerA = Customer::create(['full_name' => 'Πελάτης Α']);
        $customerB = Customer::create(['full_name' => 'Πελάτης Β']);

        $vehicleOfB = Vehicle::create([
            'customer_id' => $customerB->id,
            'plate_number' => 'ΔΕΖ-5678',
            'make' => 'Honda',
            'model' => 'Civic',
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleOfB->id,
            'problem_description' => 'Έλεγχος φρένων',
            'labor_cost' => 30,
        ]);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_work_order_can_be_created_with_single_stock_part(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΖΗΘ-9012');
        $part = Part::create([
            'code' => 'P100',
            'name' => 'Φίλτρο λαδιού',
            'quantity' => 5,
            'sale_price' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'labor_cost' => 50,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 2,
                    'unit_price' => 10,
                ],
            ],
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals(20, $workOrder->parts_cost);
        $this->assertEquals(70, $workOrder->total_cost);
    }

    public function test_work_order_can_be_created_with_multiple_part_rows_of_different_sources(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΗΘΙ-3456');
        $part = Part::create([
            'code' => 'P200',
            'name' => 'Φίλτρο αέρος',
            'quantity' => 5,
            'purchase_price' => 6,
            'sale_price' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Γενικό service',
            'labor_cost' => 50,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 2,
                    'unit_price' => 10,
                ],
                [
                    'source' => 'customer_supplied',
                    'description' => 'Μπαταρία πελάτη',
                    'quantity' => 1,
                    'unit_price' => 25,
                ],
                [
                    'source' => 'purchased_for_job',
                    'description' => 'Λάδι κινητήρα 5W-40',
                    'quantity' => 3,
                    'unit_cost' => 3,
                    'unit_price' => 5,
                ],
            ],
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals(3, WorkOrderPart::count());
        $this->assertEquals(60, $workOrder->parts_cost); // 2*10 + 1*25 + 3*5
        $this->assertEquals(110, $workOrder->total_cost); // 50 + 60

        $customerSuppliedRow = WorkOrderPart::where('source', 'customer_supplied')->first();
        $this->assertEquals(0, $customerSuppliedRow->unit_cost);
        $this->assertNull($customerSuppliedRow->part_id);

        $purchasedRow = WorkOrderPart::where('source', 'purchased_for_job')->first();
        $this->assertEquals(15, $purchasedRow->line_total);
        $this->assertNull($purchasedRow->part_id);
    }

    public function test_stock_quantity_is_decremented_only_for_from_stock_parts(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΘΙΚ-7890');
        $part = Part::create([
            'code' => 'P300',
            'name' => 'Μπουζί',
            'quantity' => 10,
            'sale_price' => 8,
        ]);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αλλαγή μπουζί',
            'labor_cost' => 20,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 3,
                    'unit_price' => 8,
                ],
                [
                    'source' => 'customer_supplied',
                    'description' => 'Καλώδιο πελάτη',
                    'quantity' => 1,
                    'unit_price' => 5,
                ],
            ],
        ]);

        $this->assertEquals(7, $part->fresh()->quantity);
    }

    public function test_quantity_accepts_half_unit_steps(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΙΚΛ-1122');
        $part = Part::create([
            'code' => 'P400',
            'name' => 'Λάδι χύμα (λίτρο)',
            'quantity' => 10,
            'sale_price' => 6,
        ]);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Συμπλήρωση λαδιού',
            'labor_cost' => 0,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 1.5,
                    'unit_price' => 6,
                ],
            ],
        ]);

        $workOrderPart = WorkOrderPart::first();

        $this->assertEquals(1.5, (float) $workOrderPart->quantity);
        $this->assertEquals(9, $workOrderPart->line_total); // 1.5 * 6
        $this->assertEquals(8.5, (float) $part->fresh()->quantity); // 10 - 1.5
    }

    public function test_work_order_stores_service_tracking_fields(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΚΛΜ-3344');

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Γενικό service',
            'labor_cost' => 60,
            'current_mileage' => 85000,
            'next_service_date' => '2026-12-01',
            'next_service_mileage' => 95000,
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $this->assertEquals(85000, $workOrder->current_mileage);
        $this->assertEquals('2026-12-01', $workOrder->next_service_date->format('Y-m-d'));
        $this->assertEquals(95000, $workOrder->next_service_mileage);
    }

    public function test_invalid_part_row_prevents_work_order_creation(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΝΞΟ-5566');
        $part = Part::create([
            'code' => 'P500',
            'name' => 'Φίλτρο καμπίνας',
            'quantity' => 5,
            'sale_price' => 12,
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'labor_cost' => 40,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 2,
                    'unit_price' => 12,
                ],
                [
                    // Missing part_id for a from_stock row — invalid.
                    'source' => 'from_stock',
                    'quantity' => 1,
                    'unit_price' => 12,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('parts.1.part_id');
        $this->assertDatabaseCount('work_orders', 0);
        $this->assertDatabaseCount('work_order_parts', 0);
        $this->assertEquals(5, $part->fresh()->quantity);
    }
}
