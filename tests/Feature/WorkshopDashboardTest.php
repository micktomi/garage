<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_an_order_checks_the_vehicle_out_of_the_shop(): void
    {
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΚΛΕ-9090');

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Σέρβις',
            'status' => WorkOrderStatus::InProgress,
        ]);

        $this->assertTrue($order->in_shop);
        $this->assertNotNull($order->checked_in_at);
        $this->assertNull($order->checked_out_at);

        $order->update(['status' => WorkOrderStatus::Completed]);

        $this->assertFalse($order->fresh()->in_shop);
        $this->assertNotNull($order->fresh()->checked_out_at);

        // An order created as already closed never counts as present.
        $imported = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Παλιά ολοκληρωμένη εντολή',
            'status' => WorkOrderStatus::Completed,
        ]);

        $this->assertFalse($imported->in_shop);
        $this->assertSame(0, WorkOrder::inShop()->count());
    }

    public function test_phone_and_tablet_user_agents_are_served_the_workshop(): void
    {
        $user = User::factory()->create();

        $agents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)',
            'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X)',
            'Mozilla/5.0 (Linux; Android 14) Mobile Safari/537.36',
        ];

        foreach ($agents as $agent) {
            $this->actingAs($user)
                ->withHeader('User-Agent', $agent)
                ->get(route('workshop.dashboard'))
                ->assertOk();
        }
    }

    /**
     * @return array{Customer, Vehicle}
     */
    private function makeCustomerAndVehicle(
        string $plate,
        string $name = 'Αντώνης Χριστοδούλου',
        string $phone = '6900000000',
    ): array {
        $customer = Customer::create(['full_name' => $name, 'phone' => $phone]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Mercedes-Benz',
            'model' => 'C-Class',
        ]);

        return [$customer, $vehicle];
    }
}
