<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\AppointmentResource\Pages\CreateAppointment;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\PartResource\Pages\ListParts;
use App\Filament\Resources\VehicleResource\Pages\CreateVehicle;
use App\Filament\Resources\VehicleResource\Pages\ListVehicles;
use App\Filament\Resources\WorkOrderResource\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrderResource\Pages\ListWorkOrders;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Livewire\Livewire;
use Tests\TestCase;

class SafeDeletesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{Customer, Vehicle, WorkOrder, Part, WorkOrderPart, Appointment} */
    private function history(): array
    {
        $customer = Customer::create(['full_name' => 'Γιώργος Ιστορικός', 'phone' => '6900000001', 'email' => 'g@example.com', 'notes' => 'VIP']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΙΣΤ-1000', 'make' => 'Fiat', 'model' => 'Panda', 'mileage' => 120000]);
        $part = Part::create(['code' => 'FLT-1', 'name' => 'Φίλτρο λαδιού', 'quantity' => 10, 'purchase_price' => 4, 'sale_price' => 9]);
        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'labor_cost' => 40,
            'status' => WorkOrderStatus::New,
        ]);
        $line = WorkOrderPart::create([
            'work_order_id' => $order->id,
            'part_id' => $part->id,
            'source' => 'from_stock',
            'quantity' => 2,
            'unit_price' => 9,
            'line_total' => 18,
        ]);
        $order->refresh();
        $order->update(['status' => WorkOrderStatus::Completed]);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        return [$customer, $vehicle, $order->refresh(), $part, $line, $appointment];
    }

    private function owner(): User
    {
        return User::factory()->create(['role' => UserRole::Owner]);
    }

    public function test_deleting_a_customer_archives_it_and_leaves_vehicles_and_work_orders_untouched(): void
    {
        [$customer, $vehicle, $order, , , $appointment] = $this->history();
        $vehicleBefore = $vehicle->fresh()->getAttributes();
        $orderBefore = $order->fresh()->getAttributes();

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertSame($vehicleBefore, $vehicle->fresh()->getAttributes());
        $this->assertSame($orderBefore, $order->fresh()->getAttributes());
        $this->assertNotNull($appointment->fresh());
        // History still names the archived owner.
        $this->assertSame('Γιώργος Ιστορικός', $vehicle->fresh()->customer->full_name);
        $this->assertSame('Γιώργος Ιστορικός', $order->fresh()->customer->full_name);
    }

    public function test_deleting_a_vehicle_archives_it_and_leaves_its_history_untouched(): void
    {
        [, $vehicle, $order, , , $appointment] = $this->history();
        $orderBefore = $order->fresh()->getAttributes();

        $vehicle->delete();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
        $this->assertSame($orderBefore, $order->fresh()->getAttributes());
        $this->assertNotNull($appointment->fresh());
        $this->assertSame('ΙΣΤ-1000', $order->fresh()->vehicle->plate_number);
    }

    public function test_deleting_a_work_order_archives_it_and_keeps_its_lines_and_stock(): void
    {
        [, , $order, $part, $line] = $this->history();
        $stockBefore = (float) $part->fresh()->quantity;

        $order->delete();

        $this->assertSoftDeleted('work_orders', ['id' => $order->id]);
        $this->assertNotNull($line->fresh());
        $this->assertSame($stockBefore, (float) $part->fresh()->quantity);
    }

    public function test_deleting_a_part_does_not_change_lines_or_totals_of_closed_orders(): void
    {
        [, , $order, $part, $line] = $this->history();
        $lineBefore = $line->fresh()->getAttributes();
        $orderBefore = $order->fresh()->only(['parts_cost', 'labor_cost', 'total_cost', 'status']);

        $part->delete();

        $this->assertSoftDeleted('parts', ['id' => $part->id]);
        $this->assertSame($lineBefore, $line->fresh()->getAttributes());
        $this->assertSame($orderBefore, $order->fresh()->only(['parts_cost', 'labor_cost', 'total_cost', 'status']));
        $this->assertSame('Φίλτρο λαδιού', $line->fresh()->part->name);
        $this->assertSame('Φίλτρο λαδιού', $line->fresh()->displayName());
    }

    public function test_restore_brings_every_record_back_exactly(): void
    {
        [$customer, $vehicle, $order, $part, $line] = $this->history();
        $snapshots = [
            [$customer, $customer->fresh()->getAttributes()],
            [$vehicle, $vehicle->fresh()->getAttributes()],
            [$order, $order->fresh()->getAttributes()],
            [$part, $part->fresh()->getAttributes()],
        ];
        $lineBefore = $line->fresh()->getAttributes();

        foreach ($snapshots as [$model]) {
            $model->delete();
        }
        foreach ($snapshots as [$model]) {
            $model::withTrashed()->findOrFail($model->getKey())->restore();
        }

        foreach ($snapshots as [$model, $before]) {
            $after = $model->fresh()->getAttributes();
            $this->assertNull($after['deleted_at']);
            unset($before['updated_at'], $after['updated_at'], $before['lock_version'], $after['lock_version']);
            $this->assertSame($before, $after, $model::class.' was not restored exactly');
        }
        $this->assertSame($lineBefore, $line->fresh()->getAttributes());
        $this->assertCount(1, $customer->fresh()->vehicles);
        $this->assertCount(1, $customer->fresh()->workOrders);
        $this->assertCount(1, $vehicle->fresh()->workOrders);
        $this->assertCount(1, $order->fresh()->workOrderParts);
    }

    public function test_database_refuses_hard_deletes_that_would_destroy_history(): void
    {
        [$customer, $vehicle, $order, $part] = $this->history();

        foreach ([['customers', $customer->id], ['vehicles', $vehicle->id], ['work_orders', $order->id], ['parts', $part->id]] as [$table, $id]) {
            try {
                DB::table($table)->where('id', $id)->delete();
                $this->fail("Hard delete of {$table}#{$id} was not blocked by the database.");
            } catch (QueryException $exception) {
                $this->assertStringContainsStringIgnoringCase('constraint', $exception->getMessage());
            }
        }

        $this->assertSame(1, Customer::count());
        $this->assertSame(1, Vehicle::count());
        $this->assertSame(1, WorkOrder::count());
        $this->assertSame(1, WorkOrderPart::count());
        $this->assertSame(1, Appointment::count());
    }

    public function test_force_delete_is_refused_for_records_with_history(): void
    {
        [$customer, $vehicle, $order, $part] = $this->history();

        foreach ([$customer, $vehicle, $order, $part] as $model) {
            $model->delete();
            $this->assertTrue($model->hasHistory(), $model::class.' should report history');
            $this->assertFalse($model->forceDelete(), $model::class.' force delete should be refused');
            $this->assertNotNull($model::withTrashed()->find($model->getKey()));
        }
    }

    public function test_force_delete_removes_records_without_history(): void
    {
        $customer = Customer::create(['full_name' => 'Χωρίς ιστορικό']);
        $vehicleOwner = Customer::create(['full_name' => 'Ιδιοκτήτης']);
        $vehicle = Vehicle::create(['customer_id' => $vehicleOwner->id, 'plate_number' => 'ΝΕΟ-0001']);
        $part = Part::create(['name' => 'Αχρησιμοποίητο', 'quantity' => 1]);

        foreach ([$customer, $vehicle, $part] as $model) {
            $this->assertFalse($model->hasHistory(), $model::class.' should have no history');
            $model->delete();
            $this->assertTrue($model->forceDelete());
            $this->assertNull($model::withTrashed()->find($model->getKey()));
        }
    }

    public function test_filament_delete_archives_and_archived_filter_restores(): void
    {
        $this->actingAs($this->owner());
        [$customer] = $this->history();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->callAction('delete');
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $customer = Customer::withTrashed()->findOrFail($customer->id);

        Livewire::test(ListCustomers::class)
            ->assertCanNotSeeTableRecords([$customer])
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$customer])
            ->assertTableActionVisible(RestoreAction::class, $customer)
            ->callTableAction(RestoreAction::class, $customer);

        $this->assertNull($customer->fresh()->deleted_at);
    }

    public function test_filament_force_delete_is_offered_only_without_history(): void
    {
        $this->actingAs($this->owner());
        [$customer, $vehicle, $order, $part] = $this->history();
        $lonelyCustomer = Customer::create(['full_name' => 'Χωρίς ιστορικό']);
        $lonelyPart = Part::create(['name' => 'Αχρησιμοποίητο', 'quantity' => 1]);
        foreach ([$customer, $vehicle, $order, $part, $lonelyCustomer, $lonelyPart] as $model) {
            $model->delete();
        }

        Livewire::test(ListCustomers::class)
            ->filterTable('trashed', false)
            ->assertTableActionHidden(ForceDeleteAction::class, $customer)
            ->assertTableActionVisible(ForceDeleteAction::class, $lonelyCustomer)
            ->callTableAction(ForceDeleteAction::class, $lonelyCustomer);
        $this->assertNull(Customer::withTrashed()->find($lonelyCustomer->id));

        Livewire::test(ListVehicles::class)
            ->filterTable('trashed', false)
            ->assertTableActionHidden(ForceDeleteAction::class, $vehicle);

        Livewire::test(ListWorkOrders::class)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$order])
            ->assertTableActionVisible(RestoreAction::class, $order)
            ->assertTableActionHidden(ForceDeleteAction::class, $order);

        Livewire::test(ListParts::class)
            ->filterTable('trashed', false)
            ->assertTableActionHidden(ForceDeleteAction::class, $part)
            ->assertTableActionVisible(ForceDeleteAction::class, $lonelyPart);
    }

    public function test_archived_records_are_not_offered_for_new_entries_in_filament_forms(): void
    {
        $this->actingAs($this->owner());
        $active = Customer::create(['full_name' => 'Ενεργός']);
        $archived = Customer::create(['full_name' => 'Αρχειοθετημένος']);
        Vehicle::create(['customer_id' => $active->id, 'plate_number' => 'ΕΝΕ-0001']);
        $archivedVehicle = Vehicle::create(['customer_id' => $active->id, 'plate_number' => 'ΑΡΧ-0001']);
        $archived->delete();
        $archivedVehicle->delete();

        $offers = fn (string $page, string $field, int $id) => Livewire::test($page)
            ->assertFormFieldExists($field, fn (Select $select) => ! array_key_exists($id, $select->getOptions()));

        $offers(CreateWorkOrder::class, 'customer_id', $archived->id);
        $offers(CreateWorkOrder::class, 'vehicle_id', $archivedVehicle->id);
        $offers(CreateAppointment::class, 'customer_id', $archived->id);
        $offers(CreateAppointment::class, 'vehicle_id', $archivedVehicle->id);
        $offers(CreateVehicle::class, 'customer_id', $archived->id);
    }

    public function test_workshop_edit_form_still_lists_an_archived_part_used_by_the_order(): void
    {
        $this->actingAs($this->owner());
        [, , $order, $part] = $this->history();
        $unused = Part::create(['name' => 'Άσχετο αρχειοθετημένο', 'quantity' => 1]);
        $order->update(['status' => WorkOrderStatus::InProgress]);
        $part->delete();
        $unused->delete();

        $this->get(route('workshop.work-orders.edit', $order))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('parts', fn ($parts) => collect($parts)->pluck('id')->contains($part->id)
                    && ! collect($parts)->pluck('id')->contains($unused->id)));
    }

    public function test_workshop_pages_keep_showing_history_of_archived_owners(): void
    {
        $this->actingAs($this->owner());
        [$customer, $vehicle, $order] = $this->history();
        $customer->delete();

        $this->get(route('workshop.vehicles.show', $vehicle))->assertOk();
        $this->get(route('workshop.work-orders.show', $order))->assertOk();
        $this->get(route('work-orders.print', $order))->assertOk()->assertSee('Γιώργος Ιστορικός');
    }
}
