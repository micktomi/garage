<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use App\Services\Assistant\AssistantEngine;
use App\Services\Assistant\DTOs\AssistantResponse;
use App\Services\Assistant\ProposalStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkshopInertiaTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $customer = Customer::create(['full_name' => 'Πελάτης δοκιμής', 'phone' => '6900000000']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'TEST100', 'make' => 'Toyota', 'model' => 'Yaris', 'mileage' => 12000]);
        $order = WorkOrder::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'Service', 'status' => 'new']);

        return [$customer, $vehicle, $order];
    }

    public function test_operational_pages_are_accessible_and_share_canonical_statuses(): void
    {
        $this->actingAs(User::factory()->create());
        [$customer, $vehicle, $order] = $this->fixture();
        $pages = ['/workshop' => 'Dashboard', '/workshop/work-orders' => 'WorkOrders/Index', '/workshop/work-orders/create' => 'WorkOrders/Form', "/workshop/work-orders/{$order->id}" => 'WorkOrders/Show', "/workshop/work-orders/{$order->id}/edit" => 'WorkOrders/Form', '/workshop/customers' => 'Customers/Index', "/workshop/customers/{$customer->id}" => 'Customers/Show', "/workshop/customers/{$customer->id}/edit" => 'Customers/Form', '/workshop/vehicles' => 'Vehicles/Index', '/workshop/vehicles/create' => 'Vehicles/Form', "/workshop/vehicles/{$vehicle->id}/edit" => 'Vehicles/Form', '/workshop/appointments' => 'Appointments/Index', '/workshop/appointments/create' => 'Appointments/Form', '/workshop/kteo' => 'Kteo/Index', '/workshop/parts' => 'Parts/Index', '/workshop/search' => 'Search', '/workshop/registration-scan' => 'RegistrationScan/Index', '/workshop/assistant' => 'Assistant/Index'];
        foreach ($pages as $url => $component) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component)->has('auth.user')->has('workOrderStatuses', 6)->where('workOrderStatuses.2.value', 'awaiting_parts')->where('workOrderStatuses.3.label', WorkOrderStatus::ReadyForPickup->label()));
        }
    }

    public function test_create_and_edit_share_catalogue_and_preserve_custom_model(): void
    {
        $this->actingAs(User::factory()->create());
        [, $vehicle] = $this->fixture();
        VehicleModel::create(['make' => 'Toyota', 'model' => 'Yaris']);
        VehicleModel::create(['make' => 'Volkswagen', 'model' => 'Golf']);
        foreach (['/workshop/vehicles/create', "/workshop/vehicles/{$vehicle->id}/edit"] as $url) {
            $this->get($url)->assertInertia(fn (Assert $page) => $page->component('Vehicles/Form')->where('modelsByMake.Toyota', ['Yaris'])->where('modelsByMake.Volkswagen', ['Golf']));
        }
        $this->put("/workshop/vehicles/{$vehicle->id}", ['customer_id' => $vehicle->customer_id, 'license_plate' => $vehicle->plate_number, 'make' => 'Toyota', 'model' => 'Custom import'])->assertSessionHasNoErrors();
        $this->assertSame('Custom import', $vehicle->fresh()->model);
    }

    public function test_edit_work_order_updates_lines_totals_and_preserves_lock(): void
    {
        $this->actingAs(User::factory()->create());
        [$customer, $vehicle, $order] = $this->fixture();
        $part = Part::create(['code' => 'P1', 'name' => 'Φίλτρο', 'quantity' => 5, 'sale_price' => 10]);
        $payload = ['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'lock_version' => 0, 'problem_description' => 'Service', 'diagnosis' => 'Έλεγχος', 'work_performed' => 'Αλλαγή φίλτρου', 'current_mileage' => 14000, 'labor_cost' => 25, 'parts' => [['source' => 'from_stock', 'part_id' => $part->id, 'quantity' => 2, 'unit_price' => 10]]];
        $this->put("/workshop/work-orders/{$order->id}", $payload)->assertSessionHasNoErrors()->assertRedirect("/workshop/work-orders/{$order->id}");
        $this->assertEquals(3, $part->fresh()->quantity);
        $this->assertEquals(45, $order->fresh()->total_cost);
        $this->assertSame('Αλλαγή φίλτρου', $order->fresh()->work_performed);
        $this->assertSame(14000, $vehicle->fresh()->mileage);
        $this->put("/workshop/work-orders/{$order->id}", $payload)->assertSessionHasErrors('lock_version');
        $this->assertEquals(3, $part->fresh()->quantity);
        $this->assertSame(1, WorkOrderPart::count());
        $payload['lock_version'] = $order->fresh()->lock_version;
        $this->put("/workshop/work-orders/{$order->id}", $payload)->assertSessionHasNoErrors();
        $this->assertEquals(3, $part->fresh()->quantity, 'Replacing the same lines must not consume twice.');
    }

    public function test_staff_completed_order_actions_are_hidden_and_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        [, , $order] = $this->fixture();
        $order->update(['status' => 'completed']);
        $this->get("/workshop/work-orders/{$order->id}")->assertInertia(fn (Assert $page) => $page->where('canEdit', false)->where('auth.canPrice', false));
        $this->get("/workshop/work-orders/{$order->id}/edit")->assertForbidden();
        $this->put("/workshop/work-orders/{$order->id}", [])->assertForbidden();
    }

    public function test_status_conflict_is_shared_as_visible_inertia_error(): void
    {
        $this->actingAs(User::factory()->create());
        [, , $order] = $this->fixture();
        $order->update(['diagnosis' => 'Changed']);
        $this->from("/workshop/work-orders/{$order->id}")->patch("/workshop/work-orders/{$order->id}/status", ['lock_version' => 0, 'status' => 'ready'])->assertSessionHasErrors('lock_version');
        $this->get("/workshop/work-orders/{$order->id}")->assertInertia(fn (Assert $page) => $page->has('errors.lock_version'));
    }

    public function test_cancelled_order_edit_does_not_restore_stock_twice(): void
    {
        $this->actingAs(User::factory()->create());
        [$customer, $vehicle, $order] = $this->fixture();
        $part = Part::create(['code' => 'P1', 'name' => 'Φίλτρο', 'quantity' => 5]);
        WorkOrderPart::create(['work_order_id' => $order->id, 'source' => 'from_stock', 'part_id' => $part->id, 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]);
        $order->update(['status' => 'cancelled']);
        $this->put("/workshop/work-orders/{$order->id}", ['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'lock_version' => $order->fresh()->lock_version, 'problem_description' => 'Service', 'parts' => [['source' => 'from_stock', 'part_id' => $part->id, 'quantity' => 1, 'unit_price' => 10]]])->assertSessionHasNoErrors();
        $this->assertEquals(5, $part->fresh()->quantity);
        $order->refresh()->update(['status' => 'new']);
        $this->assertEquals(4, $part->fresh()->quantity);
    }

    public function test_assistant_uses_shared_engine_and_executor_with_confirmation_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$customer, $vehicle] = $this->fixture();
        $proposal = app(ProposalStore::class)->create($user, 'create_work_order', 'Νέα εντολή', ['Όχημα' => $vehicle->plate_number], ['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'AI request']);
        $this->mock(AssistantEngine::class)->shouldReceive('respond')->once()->andReturn(AssistantResponse::confirmation('Ελέγξτε την πρόταση.', $proposal));
        $count = WorkOrder::count();
        $this->post('/workshop/assistant', ['action' => 'send', 'message' => 'Νέα εντολή'])->assertRedirect('/workshop/assistant');
        $this->assertSame($count, WorkOrder::count());
        $this->get('/workshop/assistant')->assertInertia(fn (Assert $page) => $page->where('proposal.token', $proposal['token']));
        $this->post('/workshop/assistant', ['action' => 'confirm'])->assertRedirect('/workshop/assistant');
        $this->assertSame($count + 1, WorkOrder::count());
        $this->post('/workshop/assistant', ['action' => 'confirm'])->assertSessionHasErrors('proposal');
        $this->assertSame($count + 1, WorkOrder::count());
    }

    public function test_assistant_cancellation_discards_shared_proposal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $store = app(ProposalStore::class);
        $proposal = $store->create($user, 'create_work_order', 'Νέα εντολή', [], []);
        $this->withSession(['workshop_assistant.'.$user->id => ['messages' => [], 'proposal' => $proposal, 'ambiguity' => null]])->post('/workshop/assistant', ['action' => 'cancel'])->assertRedirect('/workshop/assistant');
        $this->expectException(ValidationException::class);
        $store->consume($user, $proposal['token']);
    }

    public function test_appointment_edit_changes_date_time_and_status_and_rejects_another_customers_vehicle(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::create(['full_name' => 'Ραντεβού']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'APPT100']);
        $otherCustomer = Customer::create(['full_name' => 'Άλλος']);
        $other = Vehicle::create(['customer_id' => $otherCustomer->id, 'plate_number' => 'APPT200']);
        $appointment = Appointment::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'appointment_date' => now()->addDay(), 'status' => 'scheduled']);
        $payload = ['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'appointment_date' => now()->addDays(2)->toDateString(), 'appointment_time' => '19:30', 'status' => 'in_progress'];
        $this->put('/workshop/appointments/'.$appointment->id, $payload)->assertRedirect('/workshop/appointments');
        $this->assertSame($payload['appointment_date'].' 19:30', $appointment->fresh()->appointment_date->format('Y-m-d H:i'));
        $this->assertSame('in_progress', $appointment->fresh()->status);
        $this->put('/workshop/appointments/'.$appointment->id, [...$payload, 'vehicle_id' => $other->id])->assertSessionHasErrors('vehicle_id');
        $this->assertSame($vehicle->id, $appointment->fresh()->vehicle_id);
    }

    public function test_staff_edit_preserves_existing_purchase_cost_and_returns_safe_permission_page(): void
    {
        $customer = Customer::create(['full_name' => 'Δοκιμή']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'COST100']);
        $order = WorkOrder::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'Έλεγχος', 'status' => 'new']);
        $line = WorkOrderPart::create(['work_order_id' => $order->id, 'source' => 'purchased_for_job', 'description' => 'Υλικό', 'quantity' => 1, 'unit_cost' => 7, 'unit_price' => 12, 'line_total' => 12]);
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $this->put('/workshop/work-orders/'.$order->id, ['lock_version' => $order->fresh()->lock_version, 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'Διορθωμένο', 'parts' => [['id' => $line->id, 'source' => 'purchased_for_job', 'description' => 'Υλικό', 'quantity' => 1, 'unit_price' => 12, 'unit_cost' => 999]]])->assertSessionHasNoErrors();
        $this->assertEquals(7, $order->workOrderParts()->first()->unit_cost);
        $order->fresh()->update(['status' => 'completed']);
        $this->get('/workshop/work-orders/'.$order->id.'/edit')->assertForbidden()->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 403));
        $this->get('/workshop/vehicles/99999/edit')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
    }

    public function test_customer_without_vehicle_is_searchable_and_pagination_preserves_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::create(['full_name' => 'Χωρίς όχημα', 'phone' => '6901234567']);
        $this->get('/workshop/search?q=6901234567')->assertInertia(fn (Assert $page) => $page->where('customers.data.0.id', $customer->id)->has('vehicles.data', 0));
        for ($i = 1; $i <= 21; $i++) {
            Part::create(['code' => 'FILTER'.$i, 'name' => 'Φίλτρο '.$i, 'quantity' => 5, 'sale_price' => 12]);
        }
        $this->get('/workshop/parts?q=FILTER')->assertInertia(fn (Assert $page) => $page->has('parts.data', 20)->where('parts.total', 21)->where('parts.next_page_url', fn ($url) => str_contains($url, 'q=FILTER') && str_contains($url, 'page=2')));
        $this->get('/workshop/parts?q=FILTER&page=2')->assertInertia(fn (Assert $page) => $page->has('parts.data', 1));
    }

    public function test_expired_inertia_auth_redirects_to_the_existing_filament_login(): void
    {
        $this->withHeader('X-Inertia', 'true')->get('/workshop')->assertStatus(409)->assertHeader('X-Inertia-Location', '/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_inertia_status_redirect_uses_get_after_a_patch(): void
    {
        $this->actingAs(User::factory()->create());
        [, , $order] = $this->fixture();
        $this->withHeader('X-Inertia', 'true')->patch('/workshop/work-orders/'.$order->id.'/status', ['lock_version' => $order->lock_version, 'status' => 'ready'])->assertStatus(303)->assertRedirect('/workshop/work-orders/'.$order->id);
    }

    public function test_dashboard_counts_one_physical_vehicle_with_two_open_orders(): void
    {
        $this->actingAs(User::factory()->create());
        [$customer, $vehicle] = $this->fixture();
        WorkOrder::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'Δεύτερη εντολή', 'status' => 'new']);
        $this->get('/workshop')->assertInertia(fn (Assert $page) => $page->where('vehiclesInShop', 1)->where('openWorkOrders', 2));
    }

    public function test_ocr_throttling_is_a_visible_retryable_workshop_error(): void
    {
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create())->from('/workshop/registration-scan');
        for ($i = 0; $i < 6; $i++) {
            $this->post('/workshop/registration-scan/extract')->assertSessionHasErrors('registration_image');
        }
        $this->post('/workshop/registration-scan/extract')->assertStatus(303)->assertSessionHasErrors('rate_limit');
    }
}
