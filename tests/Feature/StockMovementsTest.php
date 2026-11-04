<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\PartResource\Pages\CreatePart;
use App\Filament\Resources\PartResource\Pages\EditPart;
use App\Filament\Resources\WorkOrderResource\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Models\Customer;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockMovementsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->actingAs($this->owner);
    }

    private function order(WorkOrderStatus $status = WorkOrderStatus::New): WorkOrder
    {
        static $n = 0;
        $n++;
        $customer = Customer::create(['full_name' => "Πελάτης {$n}"]);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => "ΑΠΘ-{$n}00"]);

        return WorkOrder::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'Service', 'status' => $status]);
    }

    private function part(float $quantity, string $code = 'P1'): Part
    {
        return Part::create(['code' => $code, 'name' => 'Ανταλλακτικό '.$code, 'quantity' => $quantity, 'purchase_price' => 3, 'sale_price' => 5]);
    }

    private function line(WorkOrder $order, Part $part, float $quantity): WorkOrderPart
    {
        return WorkOrderPart::create(['work_order_id' => $order->id, 'part_id' => $part->id, 'source' => 'from_stock', 'quantity' => $quantity, 'unit_price' => 5, 'line_total' => 5 * $quantity]);
    }

    /** @return list<array{string, float, ?int}> */
    private function movements(Part $part): array
    {
        return StockMovement::where('part_id', $part->id)->orderBy('id')->get()
            ->map(fn (StockMovement $m) => [$m->type->value, (float) $m->quantity, $m->work_order_id])
            ->all();
    }

    private function assertLedgerMatchesStock(): void
    {
        $this->artisan('stock:verify')->assertSuccessful()->expectsOutputToContain('Καμία απόκλιση');
    }

    public function test_creating_a_part_with_stock_records_an_in_movement(): void
    {
        $part = $this->part(10);

        $this->assertSame([['in', 10.0, null]], $this->movements($part));
        $this->assertSame($this->owner->id, StockMovement::sole()->user_id);
        $this->assertLedgerMatchesStock();
    }

    public function test_line_create_edit_and_delete_write_out_movements_against_the_order(): void
    {
        $order = $this->order();
        $part = $this->part(10);

        $line = $this->line($order, $part, 2);
        $this->assertSame(8.0, (float) $part->fresh()->quantity);

        $line->update(['quantity' => 3]);
        $this->assertSame(7.0, (float) $part->fresh()->quantity);

        $line->update(['quantity' => 1]);
        $this->assertSame(9.0, (float) $part->fresh()->quantity);

        $line->delete();
        $this->assertSame(10.0, (float) $part->fresh()->quantity);

        $this->assertSame([
            ['in', 10.0, null],
            ['out', -2.0, $order->id],
            ['out', -1.0, $order->id],
            ['out', 2.0, $order->id],
            ['out', 1.0, $order->id],
        ], $this->movements($part));
        $this->assertLedgerMatchesStock();
    }

    public function test_switching_a_line_to_another_part_returns_the_first_and_consumes_the_second(): void
    {
        $order = $this->order();
        $first = $this->part(5, 'A');
        $second = $this->part(5, 'B');
        $line = $this->line($order, $first, 2);

        $line->update(['part_id' => $second->id, 'quantity' => 3]);

        $this->assertSame(5.0, (float) $first->fresh()->quantity);
        $this->assertSame(2.0, (float) $second->fresh()->quantity);
        $this->assertLedgerMatchesStock();
    }

    public function test_switching_a_line_away_from_stock_returns_the_stock(): void
    {
        $order = $this->order();
        $part = $this->part(5);
        $line = $this->line($order, $part, 2);

        $line->update(['source' => 'customer_supplied', 'part_id' => null, 'description' => 'Από πελάτη']);

        $this->assertSame(5.0, (float) $part->fresh()->quantity);
        $this->assertLedgerMatchesStock();
    }

    public function test_cancel_returns_stock_and_reopen_consumes_it_again(): void
    {
        $order = $this->order();
        $part = $this->part(5);
        $this->line($order, $part, 2);

        $order->update(['status' => WorkOrderStatus::Cancelled]);
        $this->assertSame(5.0, (float) $part->fresh()->quantity);

        $order->update(['status' => WorkOrderStatus::New]);
        $this->assertSame(3.0, (float) $part->fresh()->quantity);

        $this->assertSame([
            ['in', 5.0, null],
            ['out', -2.0, $order->id],
            ['cancel', 2.0, $order->id],
            ['reopen', -2.0, $order->id],
        ], $this->movements($part));
        $this->assertLedgerMatchesStock();
    }

    public function test_lines_of_a_cancelled_order_do_not_move_stock(): void
    {
        $order = $this->order();
        $part = $this->part(5);
        $line = $this->line($order, $part, 2);
        $order->update(['status' => WorkOrderStatus::Cancelled]);

        $line->delete();

        $this->assertSame(5.0, (float) $part->fresh()->quantity);
        $this->assertCount(3, $this->movements($part));
        $this->assertLedgerMatchesStock();
    }

    public function test_reopen_without_enough_stock_is_refused_and_writes_nothing(): void
    {
        $order = $this->order();
        $part = $this->part(2);
        $this->line($order, $part, 2);
        $order->update(['status' => WorkOrderStatus::Cancelled]);
        $other = $this->order();
        $this->line($other, $part, 2);
        $before = StockMovement::count();

        try {
            $order->update(['status' => WorkOrderStatus::New]);
            $this->fail('Reopening must not drive stock negative.');
        } catch (ValidationException) {
        }

        $this->assertSame(WorkOrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(0.0, (float) $part->fresh()->quantity);
        $this->assertSame($before, StockMovement::count());
        $this->assertLedgerMatchesStock();
    }

    public function test_a_line_that_would_drive_stock_negative_is_rolled_back_entirely(): void
    {
        $order = $this->order();
        $part = $this->part(4);
        $line = $this->line($order, $part, 2);
        $before = StockMovement::count();

        try {
            $line->update(['quantity' => 9]);
            $this->fail('Stock went negative.');
        } catch (ValidationException) {
        }

        $this->assertSame(2.0, (float) $line->fresh()->quantity, 'the line write must be rolled back too');
        $this->assertSame(2.0, (float) $part->fresh()->quantity);
        $this->assertSame($before, StockMovement::count());
    }

    public function test_editing_a_part_quantity_directly_is_recorded_as_an_adjustment(): void
    {
        $part = $this->part(10);

        Livewire::test(EditPart::class, ['record' => $part->getRouteKey()])
            ->fillForm(['quantity' => 7])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(7.0, (float) $part->fresh()->quantity);
        $this->assertSame([['in', 10.0, null], ['adjust', -3.0, null]], $this->movements($part));
        $this->assertSame($this->owner->id, StockMovement::latest('id')->first()->user_id);
        $this->assertLedgerMatchesStock();
    }

    public function test_negative_part_quantity_is_refused_in_filament_and_in_the_model(): void
    {
        $part = $this->part(3);

        Livewire::test(EditPart::class, ['record' => $part->getRouteKey()])
            ->fillForm(['quantity' => -1])
            ->call('save')
            ->assertHasFormErrors(['quantity']);

        Livewire::test(CreatePart::class)
            ->fillForm(['code' => 'NEG', 'name' => 'Αρνητικό', 'quantity' => -2])
            ->call('create')
            ->assertHasFormErrors(['quantity']);

        try {
            $part->update(['quantity' => -1]);
            $this->fail('Model accepted a negative quantity.');
        } catch (ValidationException) {
        }

        $this->assertSame(3.0, (float) $part->fresh()->quantity);
        $this->assertSame(0, Part::where('code', 'NEG')->count());
        $this->assertLedgerMatchesStock();
    }

    public function test_filament_work_order_cannot_consume_more_than_stock_even_across_rows(): void
    {
        $customer = Customer::create(['full_name' => 'Φ']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΦΙΛ-1000']);
        $part = $this->part(3);
        $row = fn (float $qty) => ['source' => 'from_stock', 'part_id' => $part->id, 'quantity' => $qty, 'unit_price' => 5, 'line_total' => 5 * $qty];

        Livewire::test(CreateWorkOrder::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'problem_description' => 'Δοκιμή',
                'status' => WorkOrderStatus::New->value,
                'workOrderParts' => ['a' => $row(2), 'b' => $row(2)],
            ])
            ->call('create')
            ->assertHasFormErrors(['workOrderParts.a.quantity', 'workOrderParts.b.quantity']);

        $this->assertSame(0, WorkOrder::count());
        $this->assertSame(3.0, (float) $part->fresh()->quantity);
        $this->assertLedgerMatchesStock();
    }

    public function test_filament_edit_counts_what_the_order_already_holds(): void
    {
        $order = $this->order(WorkOrderStatus::InProgress);
        $part = $this->part(3);
        $line = $this->line($order, $part, 2);
        $this->assertSame(1.0, (float) $part->fresh()->quantity);

        // 2 held + 1 in stock = 3 available to this order.
        Livewire::test(EditWorkOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(["workOrderParts.record-{$line->id}.quantity" => 3])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(0.0, (float) $part->fresh()->quantity);
        $this->assertSame(3.0, (float) $line->fresh()->quantity);

        Livewire::test(EditWorkOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(["workOrderParts.record-{$line->id}.quantity" => 4])
            ->call('save')
            ->assertHasFormErrors(["workOrderParts.record-{$line->id}.quantity"]);
        $this->assertSame(0.0, (float) $part->fresh()->quantity);
        $this->assertLedgerMatchesStock();
    }

    public function test_workshop_cannot_consume_more_than_stock_and_writes_no_movement(): void
    {
        $customer = Customer::create(['full_name' => 'W']);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΓΡΑ-1000']);
        $part = $this->part(1);

        $this->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Δοκιμή',
            'parts' => [['source' => 'from_stock', 'part_id' => $part->id, 'quantity' => '2', 'unit_price' => '5']],
        ])->assertSessionHasErrors();

        $this->assertSame(1.0, (float) $part->fresh()->quantity);
        $this->assertSame([['in', 1.0, null]], $this->movements($part));
    }

    public function test_stock_verify_reports_parts_whose_stock_drifted_from_the_ledger(): void
    {
        $order = $this->order();
        $part = $this->part(5, 'DRIFT');
        $this->line($order, $part, 1);
        $this->assertLedgerMatchesStock();

        DB::table('parts')->where('id', $part->id)->update(['quantity' => 9]);

        $this->artisan('stock:verify')
            ->assertFailed()
            ->expectsOutputToContain('DRIFT');
    }
}
