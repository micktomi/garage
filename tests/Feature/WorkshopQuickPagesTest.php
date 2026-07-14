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

    public function test_customer_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('workshop.customers.store'), [
            'first_name' => 'Γιώργος',
            'last_name'  => 'Παπαδόπουλος',
            'phone'      => '6912345678',
            'email'      => 'giorgos@example.com',
        ]);

        $response->assertRedirect(route('workshop.customers.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'full_name' => 'Γιώργος Παπαδόπουλος',
            'phone'     => '6912345678',
            'email'     => 'giorgos@example.com',
        ]);
    }

    public function test_customer_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('workshop.customers.store'), [
            'first_name' => '',
            'last_name'  => '',
            'phone'      => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'phone']);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_vehicle_can_be_created(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Μαρία Ιωάννου']);

        $response = $this->actingAs($user)->post(route('workshop.vehicles.store'), [
            'customer_id'     => $customer->id,
            'license_plate'   => 'ΑΒΓ-1234',
            'make'            => 'Toyota',
            'model'           => 'Yaris',
            'year'            => 2018,
            'kteo_expires_at' => now()->addYear()->toDateString(),
        ]);

        $response->assertRedirect(route('workshop.vehicles.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vehicles', [
            'customer_id'  => $customer->id,
            'plate_number' => 'ΑΒΓ-1234',
            'make'         => 'Toyota',
            'model'        => 'Yaris',
            'year'         => 2018,
        ]);
    }

    public function test_vehicle_creation_rejects_invalid_customer_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('workshop.vehicles.store'), [
            'customer_id'   => 9999,
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
            'customer_id'     => $customer->id,
            'plate_number'    => 'ΕΞΠ-0001',
            'kteo_expires_at' => now()->subDays(5)->toDateString(),
        ]);

        $soon = Vehicle::create([
            'customer_id'     => $customer->id,
            'plate_number'    => 'ΣΟΟΝ-0002',
            'kteo_expires_at' => now()->addDays(10)->toDateString(),
        ]);

        $farFuture = Vehicle::create([
            'customer_id'     => $customer->id,
            'plate_number'    => 'ΜΑΚΡ-0003',
            'kteo_expires_at' => now()->addDays(90)->toDateString(),
        ]);

        $noKteo = Vehicle::create([
            'customer_id'  => $customer->id,
            'plate_number' => 'ΚΕΝΟ-0004',
        ]);

        $response = $this->actingAs($user)->get(route('workshop.kteo'));

        $response->assertOk();
        $response->assertSee('ΕΞΠ-0001');
        $response->assertSee('ΣΟΟΝ-0002');
        $response->assertDontSee('ΜΑΚΡ-0003');
        $response->assertDontSee('ΚΕΝΟ-0004');
    }
}
