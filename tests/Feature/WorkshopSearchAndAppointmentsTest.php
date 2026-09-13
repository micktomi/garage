<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopSearchAndAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_vehicle_by_full_plate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Νίκος Παπαδόπουλος', 'phone' => '6911112222']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'MEK-9900']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΔΕΖ-5678']);

        $response = $this->actingAs($user)->get(route('workshop.search', ['q' => 'MEK-9900']));

        $response->assertOk();
        $response->assertSee('MEK-9900');
        $response->assertDontSee('ΔΕΖ-5678');
        $response->assertSee('value="MEK-9900"', false);
    }

    public function test_search_finds_vehicle_by_partial_plate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Μαρία Ιωάννου']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΑΒΓ-1234']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΞΨΩ-9999']);

        $response = $this->actingAs($user)->get(route('workshop.search', ['q' => '1234']));

        $response->assertOk();
        $response->assertSee('ΑΒΓ-1234');
        $response->assertDontSee('ΞΨΩ-9999');
    }

    public function test_search_finds_vehicle_by_partial_customer_name(): void
    {
        $user = User::factory()->create();
        $matchingCustomer = Customer::create(['full_name' => 'Κατερίνα Χριστοδούλου']);
        $otherCustomer = Customer::create(['full_name' => 'Μαρία Παππά']);
        Vehicle::create(['customer_id' => $matchingCustomer->id, 'plate_number' => 'ΝΑΜ-1010']);
        Vehicle::create(['customer_id' => $otherCustomer->id, 'plate_number' => 'ΖΗΤ-2020']);

        $response = $this->actingAs($user)->get(route('workshop.search', ['q' => 'Χριστοδ']));

        $response->assertOk()
            ->assertSee('Κατερίνα Χριστοδούλου')
            ->assertSee('ΝΑΜ-1010')
            ->assertDontSee('ΖΗΤ-2020');
    }

    public function test_search_finds_vehicle_by_customer_phone(): void
    {
        $user = User::factory()->create();
        $matchingCustomer = Customer::create(['full_name' => 'Ελένη Αντωνίου', 'phone' => '6944556677']);
        $otherCustomer = Customer::create(['full_name' => 'Πέτρος Ιωάννου', 'phone' => '6900112233']);
        Vehicle::create(['customer_id' => $matchingCustomer->id, 'plate_number' => 'ΤΗΛ-3030']);
        Vehicle::create(['customer_id' => $otherCustomer->id, 'plate_number' => 'ΑΛΛ-4040']);

        $response = $this->actingAs($user)->get(route('workshop.search', ['q' => '6944556677']));

        $response->assertOk()
            ->assertSee('6944556677')
            ->assertSee('ΤΗΛ-3030')
            ->assertDontSee('ΑΛΛ-4040');
    }

    public function test_search_result_new_work_order_link_has_correct_customer_and_vehicle(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Αντωνίου']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΖΩΩ-4321']);

        $response = $this->actingAs($user)->get(route('workshop.search', ['q' => 'ΖΩΩ-4321']));

        $response->assertOk();
        // Blade HTML-escapes the "&" between query params, so check the
        // href contains the right path and both query values.
        $response->assertSee('/workshop/work-orders/create?customer_id='.$customer->id, false);
        $response->assertSee('vehicle_id='.$vehicle->id, false);
    }

    public function test_appointments_index_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.appointments.index'));

        $response->assertOk();
        $response->assertSee('Ραντεβού');
    }

    public function test_appointments_index_shows_today_and_future_in_order_and_hides_past(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Ελένη Σταύρου', 'phone' => '6933334444']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΠΑΣ-0001']);

        Appointment::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->subDays(3),
            'description' => 'Παλιό ραντεβού',
            'status' => 'scheduled',
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->addDays(5)->setTime(9, 0),
            'description' => 'Μελλοντικό ραντεβού',
            'status' => 'scheduled',
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->setTime(16, 0),
            'description' => 'Σημερινό ραντεβού',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)->get(route('workshop.appointments.index'));

        $response->assertOk();
        $response->assertDontSee('Παλιό ραντεβού');
        $response->assertSeeInOrder(['Σημερινό ραντεβού', 'Μελλοντικό ραντεβού']);
    }

    public function test_appointments_index_shows_empty_state_without_appointments(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.appointments.index'));

        $response->assertOk();
        $response->assertSee('Δεν υπάρχουν προγραμματισμένα ραντεβού');
    }

    public function test_appointment_can_be_created_with_matching_customer_and_vehicle(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Δημήτρης Κωστόπουλος']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΚΑΛ-7777']);

        $response = $this->actingAs($user)->post(route('workshop.appointments.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:30',
            'description' => 'Έλεγχος φρένων',
        ]);

        $response->assertRedirect(route('workshop.appointments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'description' => 'Έλεγχος φρένων',
        ]);
    }

    public function test_appointment_creation_rejects_vehicle_from_different_customer(): void
    {
        $user = User::factory()->create();
        $customerA = Customer::create(['full_name' => 'Πελάτης Α']);
        $customerB = Customer::create(['full_name' => 'Πελάτης Β']);
        $vehicleOfB = Vehicle::create(['customer_id' => $customerB->id, 'plate_number' => 'ΞΞΞ-0000']);

        $response = $this->actingAs($user)->post(route('workshop.appointments.store'), [
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleOfB->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '11:00',
        ]);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_dashboard_links_to_search_and_appointments(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.dashboard'));

        $response->assertOk();
        $response->assertSee(route('workshop.search'), false);
        $response->assertSee(route('workshop.appointments.index'), false);
    }
}
