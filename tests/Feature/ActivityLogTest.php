<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\PartResource\Pages\EditPart;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->actingAs($this->owner);
    }

    /** @return list<array{string, int, string, ?string, ?string, ?int}> */
    private function entries(): array
    {
        return ActivityLog::orderBy('id')->get()
            ->map(fn (ActivityLog $e) => [class_basename($e->subject_type), $e->subject_id, $e->field, $e->old_value, $e->new_value, $e->user_id])
            ->all();
    }

    private function order(): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Πελάτης']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΛΟΓ-1000']);

        return WorkOrder::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'x', 'status' => WorkOrderStatus::New]);
    }

    public function test_part_price_changes_are_logged_with_who_and_old_and_new_value(): void
    {
        $part = Part::create(['code' => 'P', 'name' => 'Φίλτρο', 'quantity' => 1, 'purchase_price' => 4, 'sale_price' => 9]);
        $this->assertSame([], $this->entries(), 'creating a part is not a price change');

        Livewire::test(EditPart::class, ['record' => $part->getRouteKey()])
            ->fillForm(['purchase_price' => 4.5, 'sale_price' => 9, 'name' => 'Φίλτρο λαδιού'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([['Part', $part->id, 'purchase_price', '4', '4.5', $this->owner->id]], $this->entries());
        $this->assertNotNull(ActivityLog::sole()->created_at);
    }

    public function test_work_order_status_changes_are_logged_from_the_workshop(): void
    {
        $order = $this->order();

        $this->patch(route('workshop.work-orders.status', $order), ['lock_version' => $order->lock_version, 'status' => 'in_progress'])
            ->assertRedirect();

        $this->assertSame([['WorkOrder', $order->id, 'status', 'new', 'in_progress', $this->owner->id]], $this->entries());
    }

    public function test_line_quantities_are_logged_on_create_change_and_delete(): void
    {
        $order = $this->order();
        $part = Part::create(['code' => 'P', 'name' => 'Φίλτρο', 'quantity' => 10]);

        $line = WorkOrderPart::create(['work_order_id' => $order->id, 'part_id' => $part->id, 'source' => 'from_stock', 'quantity' => 2, 'unit_price' => 5, 'line_total' => 10]);
        $line->update(['quantity' => 3, 'note' => 'άλλαξε']);
        $line->update(['note' => 'μόνο σημείωση']);
        $line->delete();

        $this->assertSame([
            ['WorkOrderPart', $line->id, 'quantity', null, '2', $this->owner->id],
            ['WorkOrderPart', $line->id, 'quantity', '2', '3', $this->owner->id],
            ['WorkOrderPart', $line->id, 'quantity', '3', null, $this->owner->id],
        ], $this->entries());
        $this->assertSame($order->id, ActivityLog::first()->work_order_id);
    }

    public function test_unlogged_fields_and_numerically_equal_values_write_nothing(): void
    {
        $part = Part::create(['code' => 'P', 'name' => 'Φίλτρο', 'quantity' => 1, 'purchase_price' => 4, 'sale_price' => 9]);
        $order = $this->order();

        $part->update(['name' => 'Άλλο όνομα', 'sale_price' => '9.00']);
        $order->update(['diagnosis' => 'Διάγνωση', 'labor_cost' => 30]);

        $this->assertSame([], $this->entries());
    }

    public function test_changes_without_a_logged_in_user_are_logged_with_a_null_user(): void
    {
        auth()->logout();
        $part = Part::create(['code' => 'P', 'name' => 'Φίλτρο', 'quantity' => 1, 'sale_price' => 9]);

        $part->update(['sale_price' => 10]);

        $this->assertSame([['Part', $part->id, 'sale_price', '9', '10', null]], $this->entries());
    }
}
