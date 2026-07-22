<?php

namespace Tests\Feature;

use App\Filament\Resources\AppointmentResource\Pages\CreateAppointment;
use App\Filament\Resources\VehicleResource\Pages\EditVehicle;
use App\Filament\Resources\VehicleResource\RelationManagers\WorkOrdersRelationManager;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Filament\Facades\Filament;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionReadinessFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-10 09:00:00');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_admin_can_access_filament_panel_in_production(): void
    {
        $admin = User::factory()->create();
        config()->set('app.env', 'production');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_vehicle_relation_manager_creates_work_order_for_owner_vehicle_and_customer(): void
    {
        $admin = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('REL-1000');
        [$otherCustomer, $otherVehicle] = $this->makeCustomerAndVehicle('REL-2000');

        $this->actingAs($admin);

        Livewire::test(WorkOrdersRelationManager::class, [
            'ownerRecord' => $vehicle,
            'pageClass' => EditVehicle::class,
        ])
            ->callTableAction(CreateAction::class, data: [
                'problem_description' => 'Έλεγχος μέσω καρτέλας οχήματος',
                'status' => 'new',
                'vehicle_id' => $otherVehicle->id,
                'customer_id' => $otherCustomer->id,
            ])
            ->assertHasNoTableActionErrors();

        $workOrder = WorkOrder::sole();

        $this->assertSame($vehicle->id, $workOrder->vehicle_id);
        $this->assertSame($customer->id, $workOrder->customer_id);
    }

    public function test_workshop_rejects_appointment_in_the_past(): void
    {
        $admin = User::factory()->create();
        [, $vehicle] = $this->makeCustomerAndVehicle('DATE-1000');

        $response = $this->actingAs($admin)->post(route('workshop.appointments.store'), [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => '2026-08-09',
            'appointment_time' => '10:30',
            'description' => 'Παρελθοντικό ραντεβού',
        ]);

        $response->assertSessionHasErrors([
            'appointment_date' => 'Η ημερομηνία δεν μπορεί να είναι στο παρελθόν.',
        ]);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_workshop_accepts_appointment_today(): void
    {
        $admin = User::factory()->create();
        [, $vehicle] = $this->makeCustomerAndVehicle('DATE-2000');

        $response = $this->actingAs($admin)->post(route('workshop.appointments.store'), [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => '2026-08-10',
            'appointment_time' => '10:30',
            'description' => 'Σημερινό ραντεβού',
        ]);

        $response->assertRedirect(route('workshop.appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'vehicle_id' => $vehicle->id,
            'appointment_date' => '2026-08-10 10:30:00',
        ]);
    }

    public function test_workshop_accepts_appointment_in_the_future(): void
    {
        $admin = User::factory()->create();
        [, $vehicle] = $this->makeCustomerAndVehicle('DATE-3000');

        $response = $this->actingAs($admin)->post(route('workshop.appointments.store'), [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => '2026-08-11',
            'appointment_time' => '10:30',
            'description' => 'Μελλοντικό ραντεβού',
        ]);

        $response->assertRedirect(route('workshop.appointments.index'));
        $this->assertDatabaseHas('appointments', [
            'vehicle_id' => $vehicle->id,
            'appointment_date' => '2026-08-11 10:30:00',
        ]);
    }

    public function test_filament_create_form_rejects_appointment_in_the_past(): void
    {
        $admin = User::factory()->create();
        [, $vehicle] = $this->makeCustomerAndVehicle('DATE-4000');

        $this->actingAs($admin);

        Livewire::test(CreateAppointment::class)
            ->fillForm([
                'customer_id' => $vehicle->customer_id,
                'vehicle_id' => $vehicle->id,
                'appointment_date' => '2026-08-09 10:30:00',
                'status' => 'scheduled',
                'description' => 'Παρελθοντικό Filament ραντεβού',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'appointment_date' => 'after_or_equal',
            ]);

        $this->assertSame(0, Appointment::count());
    }

    /**
     * @return array{Customer, Vehicle}
     */
    private function makeCustomerAndVehicle(string $plate): array
    {
        $customer = Customer::create([
            'full_name' => "Πελάτης {$plate}",
            'phone' => '6900000000',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        return [$customer, $vehicle];
    }
}
