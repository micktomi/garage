<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopQuickPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_new_customer_form_opens_and_submits_successfully(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('workshop.customers.create'))
            ->assertOk()
            ->assertSee('Νέος Πελάτης')
            ->assertSee('action="'.route('workshop.customers.store').'"', false);

        $response = $this->actingAs($user)->post(route('workshop.customers.store'), [
            'first_name' => 'Γιώργος',
            'last_name' => 'Παπαδόπουλος',
            'phone' => '6912345678',
            'email' => 'giorgos@example.com',
        ]);

        $response->assertRedirect(route('workshop.customers.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'full_name' => 'Γιώργος Παπαδόπουλος',
            'phone' => '6912345678',
            'email' => 'giorgos@example.com',
        ]);
    }

    public function test_customer_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('workshop.customers.store'), [
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'phone']);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_customers_workflow_is_reachable_from_dashboard_in_at_most_two_clicks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('workshop.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('workshop.customers.index').'"', false)
            ->assertSee('Πελάτες');

        $this->actingAs($user)->get(route('workshop.customers.index'))
            ->assertOk()
            ->assertSee('href="'.route('workshop.customers.create').'"', false)
            ->assertSee('Νέος πελάτης');

        $this->actingAs($user)->get(route('workshop.customers.create'))
            ->assertOk()
            ->assertSee('Νέος Πελάτης');
    }

    public function test_vehicle_can_be_created(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Μαρία Ιωάννου']);

        $response = $this->actingAs($user)->post(route('workshop.vehicles.store'), [
            'customer_id' => $customer->id,
            'license_plate' => 'ΑΒΓ-1234',
            'make' => 'Toyota',
            'model' => 'Yaris',
            'year' => 2018,
            'kteo_expires_at' => now()->addYear()->toDateString(),
        ]);

        $response->assertRedirect(route('workshop.vehicles.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'ΑΒΓ-1234',
            'make' => 'Toyota',
            'model' => 'Yaris',
            'year' => 2018,
        ]);
    }

    public function test_vehicle_creation_rejects_invalid_customer_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('workshop.vehicles.store'), [
            'customer_id' => 9999,
            'license_plate' => 'ΔΕΖ-5678',
        ]);

        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_kteo_page_shows_only_expired_or_expiring_within_30_days(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Νίκος Δημητρίου', 'phone' => '6900000000']);

        $expired = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΕΞΠ-0001',
            'kteo_expires_at' => now()->subDays(5)->toDateString(),
        ]);

        $soon = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΣΟΟΝ-0002',
            'kteo_expires_at' => now()->addDays(10)->toDateString(),
        ]);

        $farFuture = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΜΑΚΡ-0003',
            'kteo_expires_at' => now()->addDays(90)->toDateString(),
        ]);

        $noKteo = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΚΕΝΟ-0004',
        ]);

        $response = $this->actingAs($user)->get(route('workshop.kteo'));

        $response->assertOk();
        $response->assertSee('ΕΞΠ-0001');
        $response->assertSee('ΣΟΟΝ-0002');
        $response->assertDontSee('ΜΑΚΡ-0003');
        $response->assertDontSee('ΚΕΝΟ-0004');
    }

    public function test_customers_index_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.customers.index'));

        $response->assertOk();
        $response->assertSee('Πελάτες');
    }

    public function test_customers_index_shows_customers_and_plates(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Ελένη Κωνσταντίνου', 'phone' => '6911112222']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΧΨΩ-9999']);

        $response = $this->actingAs($user)->get(route('workshop.customers.index'));

        $response->assertOk();
        $response->assertSee('Ελένη Κωνσταντίνου');
        $response->assertSee('ΧΨΩ-9999');
    }

    public function test_customers_index_search_finds_customer_by_name(): void
    {
        $user = User::factory()->create();
        Customer::create(['full_name' => 'Δημήτρης Αντωνίου', 'phone' => '6900001111']);
        Customer::create(['full_name' => 'Κατερίνα Παππά', 'phone' => '6900002222']);

        $response = $this->actingAs($user)->get(route('workshop.customers.index', ['q' => 'Αντωνίου']));

        $response->assertOk();
        $response->assertSee('Δημήτρης Αντωνίου');
        $response->assertDontSee('Κατερίνα Παππά');
    }

    public function test_customers_index_search_finds_customer_by_phone(): void
    {
        $user = User::factory()->create();
        Customer::create(['full_name' => 'Δημήτρης Αντωνίου', 'phone' => '6944556677']);
        Customer::create(['full_name' => 'Κατερίνα Παππά', 'phone' => '6900002222']);

        $response = $this->actingAs($user)->get(route('workshop.customers.index', ['q' => '6944556677']));

        $response->assertOk();
        $response->assertSee('Δημήτρης Αντωνίου');
        $response->assertSee('6944556677');
        $response->assertDontSee('Κατερίνα Παππά');
    }

    public function test_customers_index_search_finds_customer_by_plate(): void
    {
        $user = User::factory()->create();
        $customerA = Customer::create(['full_name' => 'Πελάτης Α']);
        $customerB = Customer::create(['full_name' => 'Πελάτης Β']);
        Vehicle::create(['customer_id' => $customerA->id, 'plate_number' => 'ΑΑΑ-1111']);
        Vehicle::create(['customer_id' => $customerB->id, 'plate_number' => 'ΒΒΒ-2222']);

        $response = $this->actingAs($user)->get(route('workshop.customers.index', ['q' => 'ΑΑΑ-1111']));

        $response->assertOk();
        $response->assertSee('Πελάτης Α');
        $response->assertDontSee('Πελάτης Β');
    }

    public function test_dashboard_primary_action_links_to_new_work_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.dashboard'));

        $response->assertOk();
        $response->assertSee(route('workshop.work-orders.create'), false);
    }

    public function test_kteo_page_has_call_and_sms_actions_for_customer_with_phone(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Σπύρος Λαμπρόπουλος', 'phone' => '6944445555']);
        Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΚΤΕ-0001',
            'kteo_expires_at' => now()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('workshop.kteo'));

        $response->assertOk();
        $response->assertSee('tel:6944445555', false);
        $response->assertSee('sms:6944445555', false);
        $response->assertSee('Αντιγραφή μηνύματος');
    }

    public function test_kteo_page_no_longer_shows_filament_edit_action(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Άννα Βασιλείου', 'phone' => '6955556666']);
        Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΚΤΕ-0002',
            'kteo_expires_at' => now()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('workshop.kteo'));

        $response->assertOk();
        $response->assertDontSee('Πλήρης επεξεργασία');
        $response->assertDontSee('/admin/vehicles', false);
    }
}
