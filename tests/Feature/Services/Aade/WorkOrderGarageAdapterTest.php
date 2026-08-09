<?php

namespace Tests\Feature\Services\Aade;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\Aade\WorkOrderGarageAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class WorkOrderGarageAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_new_client_data_maps_branch_and_vehicle_plate(): void
    {
        config(['services.aade.branch' => 2]);

        $workOrder = $this->makeWorkOrder('ΑΒΓ1234');

        $data = (new WorkOrderGarageAdapter($workOrder))->toNewClientData();

        $this->assertSame(2, $data->branch);
        $this->assertSame('ΑΒΓ1234', $data->useCase->vehicleRegistrationNumber);
        $this->assertNull($data->useCase->vehicleCategory);
        $this->assertNull($data->customerVatNumber);
    }

    public function test_to_update_client_data_is_not_implemented_yet(): void
    {
        $workOrder = $this->makeWorkOrder('ΑΒΓ1234');

        $this->expectException(RuntimeException::class);

        (new WorkOrderGarageAdapter($workOrder))->toUpdateClientData(100000000830764);
    }

    private function makeWorkOrder(string $plate): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Test Customer']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
        ]);

        return WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Test service',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
        ]);
    }
}
