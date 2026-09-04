<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A double-clicked or re-POSTed create form must not produce a second work
 * order.
 */
class WorkOrderIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_replaying_the_same_submission_returns_the_original_work_order(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->makeVehicle('ΔΙΠ-1000');
        $payload = $this->payload($vehicle, 'tok-replay-0000000000000000000001');

        $first = $this->actingAs($user)->post(route('workshop.work-orders.store'), $payload);
        $second = $this->actingAs($user)->post(route('workshop.work-orders.store'), $payload);

        $this->assertSame(1, WorkOrder::count(), 'A replayed submission must not create a second work order.');

        $workOrder = WorkOrder::sole();
        $first->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $second->assertRedirect(route('workshop.work-orders.show', $workOrder));
    }

    public function test_a_replayed_submission_does_not_consume_stock_twice(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->makeVehicle('ΔΙΠ-3000');
        $part = Part::create(['code' => 'ΔΙΠ-1', 'name' => 'Φίλτρο', 'quantity' => 10, 'sale_price' => 5]);

        $payload = $this->payload($vehicle, 'tok-replay-0000000000000000000003') + [
            'parts' => [[
                'source' => 'from_stock',
                'part_id' => $part->id,
                'quantity' => '2',
                'unit_price' => '5',
            ]],
        ];

        $this->actingAs($user)->post(route('workshop.work-orders.store'), $payload);
        $this->actingAs($user)->post(route('workshop.work-orders.store'), $payload);

        $this->assertSame(1, WorkOrder::count());
        $this->assertSame(8.0, (float) $part->fresh()->quantity);
    }

    public function test_two_genuinely_different_submissions_still_create_two_work_orders(): void
    {
        $user = User::factory()->create();
        $vehicle = $this->makeVehicle('ΔΙΠ-4000');

        $this->actingAs($user)->post(route('workshop.work-orders.store'), $this->payload($vehicle, 'tok-a-000000000000000000000001'));
        $this->actingAs($user)->post(route('workshop.work-orders.store'), $this->payload($vehicle, 'tok-b-000000000000000000000002'));

        $this->assertSame(2, WorkOrder::count());
    }

    public function test_the_create_form_carries_an_idempotency_token(): void
    {
        $user = User::factory()->create();
        $this->makeVehicle('ΔΙΠ-5000');

        $this->actingAs($user)
            ->get(route('workshop.work-orders.create'))
            ->assertOk()
            ->assertSee('name="idempotency_key"', false);
    }

    public function test_the_database_itself_refuses_a_duplicate_idempotency_key(): void
    {
        $this->assertTrue(
            Schema::hasColumn('work_orders', 'idempotency_key'),
            'The replay guarantee has to live in the database, not in a read-then-write check.',
        );

        $vehicle = $this->makeVehicle('ΔΙΠ-6000');
        $attributes = [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => 'new',
            'idempotency_key' => 'tok-unique-00000000000000000001',
        ];

        WorkOrder::create($attributes);

        $this->expectException(QueryException::class);

        WorkOrder::create([...$attributes, 'vehicle_id' => $this->makeVehicle('ΔΙΠ-6001')->id]);
    }

    /** @return array<string, mixed> */
    private function payload(Vehicle $vehicle, string $token): array
    {
        return [
            'idempotency_key' => $token,
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Δεν παίρνει μπρος',
        ];
    }

    private function makeVehicle(string $plate): Vehicle
    {
        $customer = Customer::firstOrCreate(['full_name' => 'Πελάτης ΔΙΠ']);

        return Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
        ]);
    }
}
