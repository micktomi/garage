<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\PartResource\Pages\EditPart;
use App\Filament\Resources\WorkOrderResource;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrderResource\Pages\ListWorkOrders;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Until now every authenticated user was, in effect, the owner: any account
 * could bulk-delete fiscally significant work orders, rewrite purchase costs,
 * or amend an order that had already been completed.
 *
 * Two roles, no package. Owner keeps everything it had — that is the
 * compatibility requirement, and why the column defaults to owner. Staff is
 * the day-to-day account: it can run the shop, but not destroy records, not
 * administer costs, and not amend an order that has already been completed.
 */
class AuthorizationRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_account_is_an_owner_unless_it_is_told_otherwise(): void
    {
        // The single-owner installation that exists today must keep working
        // without anyone editing a database row.
        $this->assertSame(UserRole::Owner, User::factory()->create()->role);
    }

    public function test_both_roles_still_reach_filament_and_the_workshop(): void
    {
        foreach ([$this->owner(), $this->staff()] as $user) {
            $this->actingAs($user)->get('/admin')->assertOk();
            $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
            $this->actingAs($user)->get(route('workshop.work-orders.create'))->assertOk();
        }
    }

    public function test_only_the_owner_may_delete_records(): void
    {
        $owner = $this->owner();
        $staff = $this->staff();

        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress, 'ΡΟΛ-0001');
        $models = [
            $workOrder,
            $workOrder->customer,
            $workOrder->vehicle,
            Part::create(['code' => 'ΡΟΛ-1', 'name' => 'Φίλτρο', 'quantity' => 1]),
            Appointment::create([
                'customer_id' => $workOrder->customer_id,
                'vehicle_id' => $workOrder->vehicle_id,
                'appointment_date' => now()->addDay(),
            ]),
        ];

        foreach ($models as $model) {
            $this->assertTrue(Gate::forUser($owner)->allows('delete', $model), $model::class.' must stay deletable by the owner.');
            $this->assertFalse(Gate::forUser($staff)->allows('delete', $model), $model::class.' must not be deletable by staff.');
            $this->assertTrue(Gate::forUser($owner)->allows('deleteAny', $model::class));
            $this->assertFalse(Gate::forUser($staff)->allows('deleteAny', $model::class));
        }
    }

    public function test_staff_never_sees_a_delete_control_in_filament(): void
    {
        $staff = $this->staff();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress, 'ΡΟΛ-0002');
        $this->actingAs($staff);

        $this->assertFalse(WorkOrderResource::canDelete($workOrder));
        $this->assertFalse(WorkOrderResource::canDeleteAny());

        Livewire::actingAs($staff)
            ->test(EditWorkOrder::class, ['record' => $workOrder->id])
            ->assertActionHidden('delete');

        Livewire::actingAs($staff)
            ->test(ListWorkOrders::class)
            ->assertTableBulkActionHidden('delete');
    }

    public function test_the_owner_still_sees_the_delete_controls(): void
    {
        $owner = $this->owner();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress, 'ΡΟΛ-0003');
        $this->actingAs($owner);

        $this->assertTrue(WorkOrderResource::canDelete($workOrder));

        Livewire::actingAs($owner)
            ->test(EditWorkOrder::class, ['record' => $workOrder->id])
            ->assertActionVisible('delete');
    }

    public function test_only_the_owner_may_administer_pricing(): void
    {
        $this->assertTrue(Gate::forUser($this->owner())->allows('administer-pricing'));
        $this->assertFalse(Gate::forUser($this->staff())->allows('administer-pricing'));
    }

    public function test_filament_locks_the_price_catalogue_for_staff(): void
    {
        $part = Part::create(['code' => 'ΡΟΛ-2', 'name' => 'Τακάκια', 'quantity' => 4, 'purchase_price' => 10, 'sale_price' => 20]);

        Livewire::actingAs($this->staff())
            ->test(EditPart::class, ['record' => $part->id])
            ->assertFormFieldIsDisabled('purchase_price')
            ->assertFormFieldIsDisabled('sale_price');

        Livewire::actingAs($this->owner())
            ->test(EditPart::class, ['record' => $part->id])
            ->assertFormFieldIsEnabled('purchase_price')
            ->assertFormFieldIsEnabled('sale_price');
    }

    public function test_the_workshop_cost_box_is_read_only_for_staff(): void
    {
        $this->makeVehicle('ΡΟΛ-0011');

        $staffForm = $this->actingAs($this->staff())
            ->get(route('workshop.work-orders.create'))
            ->assertOk()
            ->getContent();
        $ownerForm = $this->actingAs($this->owner())
            ->get(route('workshop.work-orders.create'))
            ->assertOk()
            ->getContent();

        $costInput = "'parts['+index+'][unit_cost]'";

        $this->assertMatchesRegularExpression(
            '/'.preg_quote($costInput, '/').'.{0,300}?readonly/s',
            $staffForm,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/'.preg_quote($costInput, '/').'.{0,300}?readonly/s',
            $ownerForm,
        );
    }

    public function test_a_cost_typed_by_staff_is_replaced_by_the_catalogue_cost(): void
    {
        $vehicle = $this->makeVehicle('ΡΟΛ-0004');
        $part = Part::create(['code' => 'ΡΟΛ-3', 'name' => 'Λάδι', 'quantity' => 10, 'purchase_price' => 7.5, 'sale_price' => 15]);

        $this->actingAs($this->staff())
            ->post(route('workshop.work-orders.store'), $this->storePayload($vehicle, $part, unitCost: '999'))
            ->assertRedirect();

        $this->assertSame(7.5, (float) WorkOrder::sole()->workOrderParts()->sole()->unit_cost);
    }

    public function test_the_owner_may_still_record_a_cost_by_hand(): void
    {
        $vehicle = $this->makeVehicle('ΡΟΛ-0005');
        $part = Part::create(['code' => 'ΡΟΛ-4', 'name' => 'Λάδι', 'quantity' => 10, 'purchase_price' => 7.5, 'sale_price' => 15]);

        $this->actingAs($this->owner())
            ->post(route('workshop.work-orders.store'), $this->storePayload($vehicle, $part, unitCost: '9.25'))
            ->assertRedirect();

        $this->assertSame(9.25, (float) WorkOrder::sole()->workOrderParts()->sole()->unit_cost);
    }

    public function test_staff_may_still_complete_an_open_work_order(): void
    {
        $staff = $this->staff();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup, 'ΡΟΛ-0006');

        $this->actingAs($staff)->patch(route('workshop.work-orders.status', $workOrder), [
            'lock_version' => $workOrder->lock_version,
            'status' => WorkOrderStatus::Completed->value,
        ])->assertRedirect();

        $this->assertSame(WorkOrderStatus::Completed, $workOrder->fresh()->status);
    }

    public function test_staff_may_not_amend_an_order_that_has_already_been_filed(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup, 'ΡΟΛ-0007');
        $workOrder->update(['status' => WorkOrderStatus::Completed]);
        $workOrder = $workOrder->fresh();

        $this->actingAs($this->staff())->patch(route('workshop.work-orders.status', $workOrder), [
            'lock_version' => $workOrder->lock_version,
            'status' => WorkOrderStatus::InProgress->value,
        ])->assertForbidden();

        $this->assertSame(WorkOrderStatus::Completed, $workOrder->fresh()->status);
    }

    public function test_staff_is_not_offered_status_buttons_that_would_only_403(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup, 'ΡΟΛ-0012');
        $workOrder->update(['status' => WorkOrderStatus::Completed]);

        $staffPage = $this->actingAs($this->staff())
            ->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk();
        $ownerPage = $this->actingAs($this->owner())
            ->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk();

        $this->assertCount(0, $this->statusForms($staffPage->getContent()));
        $this->assertCount(5, $this->statusForms($ownerPage->getContent()));
        $staffPage->assertSee('μόνο από τον ιδιοκτήτη');
    }

    private function statusForms(string $html): \DOMNodeList
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        return (new \DOMXPath($document))->query(
            '//form[contains(concat(" ", normalize-space(@class), " "), " wos-status-form ")]'
        );
    }

    public function test_the_owner_may_amend_an_order_that_has_already_been_filed(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup, 'ΡΟΛ-0008');
        $workOrder->update(['status' => WorkOrderStatus::Completed]);
        $workOrder = $workOrder->fresh();

        $this->actingAs($this->owner())->patch(route('workshop.work-orders.status', $workOrder), [
            'lock_version' => $workOrder->lock_version,
            'status' => WorkOrderStatus::InProgress->value,
        ])->assertRedirect();

        $this->assertSame(WorkOrderStatus::InProgress, $workOrder->fresh()->status);
    }

    public function test_filament_refuses_a_staff_amendment_of_a_filed_order(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup, 'ΡΟΛ-0009');
        $workOrder->update(['status' => WorkOrderStatus::Completed]);

        Livewire::actingAs($this->staff())
            ->test(EditWorkOrder::class, ['record' => $workOrder->id])
            ->fillForm(['diagnosis' => 'Νέα διάγνωση'])
            ->call('save');

        $this->assertNull($workOrder->fresh()->diagnosis);
    }

    public function test_filament_still_lets_staff_edit_an_open_order(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress, 'ΡΟΛ-0010');

        Livewire::actingAs($this->staff())
            ->test(EditWorkOrder::class, ['record' => $workOrder->id])
            ->fillForm(['diagnosis' => 'Φθαρμένα τακάκια'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Φθαρμένα τακάκια', $workOrder->fresh()->diagnosis);
    }

    private function owner(): User
    {
        return User::factory()->create(['role' => UserRole::Owner]);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => UserRole::Staff]);
    }

    /** @return array<string, mixed> */
    private function storePayload(Vehicle $vehicle, Part $part, string $unitCost): array
    {
        return [
            'idempotency_key' => 'tok-role-'.$vehicle->id.'-'.random_int(1000, 9999),
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'parts' => [[
                'source' => 'from_stock',
                'part_id' => $part->id,
                'quantity' => '1',
                'unit_cost' => $unitCost,
                'unit_price' => '15',
            ]],
        ];
    }

    private function makeVehicle(string $plate): Vehicle
    {
        $customer = Customer::create(['full_name' => 'Πελάτης '.$plate]);

        return Vehicle::create(['customer_id' => $customer->id, 'plate_number' => $plate]);
    }

    private function makeWorkOrder(WorkOrderStatus $status, string $plate): WorkOrder
    {
        $vehicle = $this->makeVehicle($plate);

        return WorkOrder::create([
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => $status,
        ]);
    }
}
