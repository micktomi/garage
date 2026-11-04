<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrderResource\Pages\ListWorkOrders;
use App\Filament\Resources\WorkOrderResource\RelationManagers\PaymentsRelationManager;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\PaymentService;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->actingAs($this->owner);
    }

    private function order(float $total, WorkOrderStatus $status = WorkOrderStatus::Completed, ?Customer $customer = null): WorkOrder
    {
        static $n = 0;
        $n++;
        $customer ??= Customer::create(['full_name' => "Πελάτης {$n}"]);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => "ΠΛΗ-{$n}000"]);

        return WorkOrder::create([
            'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'x',
            'labor_cost' => $total, 'parts_cost' => 0, 'total_cost' => $total, 'status' => $status,
        ]);
    }

    private function pay(WorkOrder $order, float $amount, PaymentMethod $method = PaymentMethod::Cash): Payment
    {
        return app(PaymentService::class)->record($order, $amount, $method);
    }

    private function refusesPayment(WorkOrder $order, float $amount, string $expected): void
    {
        $before = Payment::count();
        try {
            $this->pay($order, $amount);
            $this->fail("A payment of {$amount} was accepted.");
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($expected, collect($exception->errors())->flatten()->implode(' '));
        }
        $this->assertSame($before, Payment::count());
    }

    public function test_an_order_without_payments_is_unpaid(): void
    {
        $order = $this->order(100);

        $this->assertSame(0.0, $order->paidAmount());
        $this->assertSame(100.0, $order->balance());
        $this->assertSame(PaymentStatus::Unpaid, $order->paymentStatus());
    }

    public function test_partial_payment(): void
    {
        $order = $this->order(100);

        $payment = $this->pay($order, 40, PaymentMethod::Card);

        $this->assertSame(40.0, $order->paidAmount());
        $this->assertSame(60.0, $order->balance());
        $this->assertSame(PaymentStatus::Partial, $order->paymentStatus());
        $this->assertSame($this->owner->id, $payment->user_id);
        $this->assertSame(PaymentMethod::Card, $payment->method);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_overpayment_is_refused(): void
    {
        $order = $this->order(100);
        $this->pay($order, 40);

        $this->refusesPayment($order, 60.01, 'υπόλοιπο');
        $this->assertSame(60.0, $order->balance());
    }

    public function test_settling_the_balance_marks_the_order_paid(): void
    {
        $order = $this->order(100);
        $this->pay($order, 40);

        $this->pay($order, 60, PaymentMethod::Bank);

        $this->assertSame(0.0, $order->balance());
        $this->assertSame(PaymentStatus::Paid, $order->paymentStatus());
        $this->refusesPayment($order, 0.01, 'υπόλοιπο');
    }

    public function test_amount_must_be_positive_and_cancelled_orders_take_no_payment(): void
    {
        $order = $this->order(100);
        $this->refusesPayment($order, 0, 'μεγαλύτερο του μηδενός');
        $this->refusesPayment($order, -5, 'μεγαλύτερο του μηδενός');

        $cancelled = $this->order(100, WorkOrderStatus::Cancelled);
        $this->refusesPayment($cancelled, 10, 'ακυρωμένη');
    }

    public function test_a_deposit_on_an_open_order_is_allowed(): void
    {
        $order = $this->order(100, WorkOrderStatus::InProgress);

        $this->pay($order, 30);

        $this->assertSame(PaymentStatus::Partial, $order->paymentStatus());
    }

    public function test_lowering_the_total_after_payment_shows_overpayment_and_is_not_blocked(): void
    {
        $order = $this->order(100);
        $this->pay($order, 100);

        $order->update(['labor_cost' => 80, 'total_cost' => 80]);

        $this->assertSame(80.0, (float) $order->fresh()->total_cost);
        $this->assertSame(-20.0, $order->fresh()->balance());
        $this->assertSame(PaymentStatus::Overpaid, $order->fresh()->paymentStatus());
        $this->assertSame('Υπερπληρωμή 20,00 €', $order->fresh()->paymentStatusLabel());
    }

    public function test_customer_balance_counts_only_completed_active_orders(): void
    {
        $customer = Customer::create(['full_name' => 'Οφειλέτης']);
        $a = $this->order(100, customer: $customer);
        $this->pay($a, 40);                                                  // owes 60
        $this->order(50, customer: $customer);                               // owes 50
        $this->pay($this->order(80, WorkOrderStatus::InProgress, $customer), 10); // open: not a debt yet
        $this->order(70, WorkOrderStatus::Cancelled, $customer);            // cancelled: never a debt
        $this->order(30, customer: $customer)->delete();                     // archived: excluded
        $settled = $this->order(20, customer: $customer);
        $this->pay($settled, 20);                                            // owes 0
        $this->order(999);                                                   // someone else

        $this->assertSame(110.0, $customer->balance());
        $this->assertSame(110.0, (float) Customer::withBalance()->find($customer->id)->balance);
    }

    public function test_completed_at_is_set_on_completion_and_cleared_on_reopen(): void
    {
        $order = $this->order(10, WorkOrderStatus::ReadyForPickup);
        $this->assertNull($order->completed_at);

        $this->travelTo(now()->subDays(5));
        $order->update(['status' => WorkOrderStatus::Completed]);
        $this->travelBack();
        $this->assertSame(5, $order->fresh()->daysSinceCompletion());

        $order->update(['status' => WorkOrderStatus::InProgress]);
        $this->assertNull($order->fresh()->completed_at);
        $this->assertNull($order->fresh()->daysSinceCompletion());
    }

    public function test_filament_relation_manager_records_payments_and_refuses_overpayment(): void
    {
        $order = $this->order(100);

        Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => EditWorkOrder::class])
            ->callTableAction(CreateAction::class, data: ['amount' => 120, 'method' => PaymentMethod::Cash->value, 'paid_at' => now()->toDateTimeString()])
            ->assertHasTableActionErrors(['amount']);
        $this->assertSame(0, Payment::count());

        Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => EditWorkOrder::class])
            ->callTableAction(CreateAction::class, data: ['amount' => 40, 'method' => PaymentMethod::Card->value, 'paid_at' => now()->toDateTimeString(), 'note' => 'Προκαταβολή'])
            ->assertHasNoTableActionErrors();

        $payment = Payment::sole();
        $this->assertSame(40.0, (float) $payment->amount);
        $this->assertSame($this->owner->id, $payment->user_id);
        $this->assertSame('Προκαταβολή', $payment->note);
    }

    public function test_payments_cannot_be_edited_and_only_the_owner_deletes_them_with_a_log_entry(): void
    {
        $order = $this->order(100);
        $payment = $this->pay($order, 40);
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($staff);
        Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => EditWorkOrder::class])
            ->assertTableActionDoesNotExist(EditAction::class)
            ->assertTableActionHidden(DeleteAction::class, $payment);
        $this->assertFalse($staff->can('delete', $payment));
        $this->assertFalse($staff->can('update', $payment));

        $this->actingAs($this->owner);
        $this->assertFalse($this->owner->can('update', $payment));
        Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $order, 'pageClass' => EditWorkOrder::class])
            ->assertTableActionDoesNotExist(EditAction::class)
            ->callTableAction(DeleteAction::class, $payment);

        $this->assertSame(0, Payment::count());
        $log = ActivityLog::where('subject_type', $payment->getMorphClass())->where('field', 'amount')->sole();
        $this->assertSame(['40', null, $this->owner->id, $order->id], [$log->old_value, $log->new_value, $log->user_id, $log->work_order_id]);
        $this->assertSame(100.0, $order->balance());
    }

    public function test_debts_filter_lists_completed_orders_with_a_balance(): void
    {
        $debt = $this->order(100);
        $this->pay($debt, 30);
        $paid = $this->order(50);
        $this->pay($paid, 50);
        $open = $this->order(80, WorkOrderStatus::InProgress);

        Livewire::test(ListWorkOrders::class)
            ->filterTable('debts')
            ->assertCanSeeTableRecords([$debt])
            ->assertCanNotSeeTableRecords([$paid, $open])
            ->assertTableColumnStateSet('balance', 70, $debt);
    }

    public function test_workshop_order_page_shows_paid_balance_and_status(): void
    {
        $order = $this->order(100);
        $this->pay($order, 40);

        $this->get(route('workshop.work-orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('payment.paid', 40)
                ->where('payment.balance', 60)
                ->where('payment.status', 'partial')
                ->where('payment.label', 'Μερική εξόφληση'));
    }

    public function test_filament_pages_with_payment_columns_render(): void
    {
        $order = $this->order(100);
        $this->pay($order, 40);

        $this->get('/admin/work-orders')->assertOk()->assertSee('Υπόλοιπο')->assertSee('Μερική εξόφληση');
        $this->get('/admin/work-orders?tableFilters[debts][isActive]=1')->assertOk();
        $this->get('/admin/customers?tableSortColumn=balance&tableSortDirection=desc')->assertOk()->assertSee('Υπόλοιπο');
        // Relation managers are lazy-loaded Livewire components.
        $this->get("/admin/work-orders/{$order->id}/edit")->assertOk()->assertSeeLivewire(PaymentsRelationManager::class);
        $this->get("/admin/work-orders/{$order->id}")->assertOk()->assertSeeLivewire(PaymentsRelationManager::class);
    }

    public function test_purge_removes_payments(): void
    {
        $this->pay($this->order(100), 10);

        $this->artisan('garage:purge-business-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, DB::table('payments')->count());
    }
}
