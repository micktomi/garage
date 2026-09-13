<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_can_be_created_with_vin_and_mileage(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);

        $response = $this->actingAs($user)->post(route('workshop.vehicles.store'), [
            'customer_id' => $customer->id,
            'license_plate' => 'ΑΒΓ-1234',
            'make' => 'Toyota',
            'model' => 'Yaris',
            'vin' => 'WVWZZZ1JZXW000001',
            'mileage' => 85000,
        ]);

        $vehicle = Vehicle::first();

        $this->assertNotNull($vehicle);
        $response->assertRedirect(route('workshop.vehicles.create'));
        $this->assertEquals('WVWZZZ1JZXW000001', $vehicle->vin);
        $this->assertEquals(85000, $vehicle->mileage);
        $this->assertTrue($vehicle->customer->is($customer));
    }

    public function test_vehicle_store_and_update_accept_models_outside_the_catalog(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        VehicleModel::create(['make' => 'Toyota', 'model' => 'Yaris']);

        $this->actingAs($user)->post(route('workshop.vehicles.store'), [
            'customer_id' => $customer->id,
            'license_plate' => 'ΕΛΕ-1234',
            'make' => 'Toyota',
            'model' => 'Custom Conversion',
        ])->assertRedirect(route('workshop.vehicles.create'));

        $vehicle = Vehicle::where('plate_number', 'ΕΛΕ-1234')->firstOrFail();
        $this->assertSame('Custom Conversion', $vehicle->model);

        $this->actingAs($user)->put(route('workshop.vehicles.update', $vehicle), [
            'customer_id' => $customer->id,
            'license_plate' => 'ΕΛΕ-1234',
            'make' => 'Toyota',
            'model' => 'Workshop Edition',
        ])->assertRedirect(route('workshop.vehicles.edit', $vehicle));

        $this->assertSame('Workshop Edition', $vehicle->fresh()->model);
    }

    public function test_vehicle_edit_form_loads_with_existing_data(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΔΕΖ-5678',
            'make' => 'Honda',
            'model' => 'Civic',
            'vin' => 'JHMFA16588S000001',
            'mileage' => 42000,
        ]);

        $response = $this->actingAs($user)->get(route('workshop.vehicles.edit', $vehicle));

        $response->assertOk();
        $response->assertSee('JHMFA16588S000001');
        $response->assertSee('42000');
    }

    public function test_vehicle_update_allows_correcting_mileage_downward(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΖΗΘ-9012',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'mileage' => 999000, // obvious data-entry mistake
        ]);

        $response = $this->actingAs($user)->put(route('workshop.vehicles.update', $vehicle), [
            'customer_id' => $customer->id,
            'license_plate' => 'ΖΗΘ-9012',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'mileage' => 99000,
        ]);

        $response->assertRedirect(route('workshop.vehicles.edit', $vehicle));
        $this->assertEquals(99000, $vehicle->fresh()->mileage);
    }

    public function test_create_form_does_not_expose_existing_vehicle_selection(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΚΛΜ-1122',
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $response = $this->actingAs($user)->get(route('workshop.vehicles.create'));

        $response->assertOk();
        // /workshop/vehicles/create is only for entering a brand-new plate
        // manually. Selecting an existing vehicle (and redirecting to its
        // edit page) belongs to the work-order creation flow only.
        $response->assertDontSee('existing_vehicle_id', false);
        $response->assertDontSee('VEHICLES_BY_CUSTOMER', false);
    }

    public function test_vehicle_store_rejects_duplicate_plate_for_same_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΝΞΟ-3344',
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $response = $this->actingAs($user)->post(route('workshop.vehicles.store'), [
            'customer_id' => $customer->id,
            'license_plate' => 'ΝΞΟ-3344',
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        $response->assertSessionHasErrors('license_plate');
        $this->assertEquals(1, Vehicle::where('plate_number', 'ΝΞΟ-3344')->count());
    }

    public function test_vehicle_update_keeps_own_plate_unique_check(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΗΘΙ-3456',
            'make' => 'Toyota',
            'model' => 'Auris',
        ]);

        $response = $this->actingAs($user)->put(route('workshop.vehicles.update', $vehicle), [
            'customer_id' => $customer->id,
            'license_plate' => 'ΗΘΙ-3456',
            'make' => 'Toyota',
            'model' => 'Auris',
            'vin' => 'NEWVIN000000000001',
        ]);

        $response->assertSessionDoesntHaveErrors('license_plate');
        $this->assertEquals('NEWVIN000000000001', $vehicle->fresh()->vin);
    }
}
