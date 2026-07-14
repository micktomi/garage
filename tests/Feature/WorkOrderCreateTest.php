<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderCreateTest extends TestCase
{
    use RefreshDatabase;

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
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΑΒΓ-1234',
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

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

    public function test_existing_work_order_creation_flow_still_works_with_part_from_stock(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Μαρία Ιωάννου']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΖΗΘ-9012',
            'make' => 'Nissan',
            'model' => 'Micra',
        ]);
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
            'part' => [
                'source' => 'from_stock',
                'part_id' => $part->id,
                'quantity' => 2,
                'unit_price' => 10,
            ],
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals(20, $workOrder->parts_cost);
        $this->assertEquals(70, $workOrder->total_cost);
    }
}
