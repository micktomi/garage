<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AccessAndProductionGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function order(WorkOrderStatus $status, string $plate = 'ΑΚΣ-1000'): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Πελάτης '.$plate]);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => $plate]);

        return WorkOrder::create(['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'problem_description' => 'x', 'status' => $status]);
    }

    /** A row whose role is not one the app knows (typo, legacy value, manual SQL). */
    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['role' => $role]);

        return User::findOrFail($user->id);
    }

    public static function invalidRoles(): array
    {
        return [['mechanic'], [''], ['OWNER']];
    }

    /** @dataProvider invalidRoles */
    public function test_a_user_without_a_valid_role_is_refused_by_the_panel_and_the_workshop(string $role): void
    {
        $user = $this->userWithRole($role);
        $order = $this->order(WorkOrderStatus::New);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get(route('workshop.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('workshop.work-orders.show', $order))->assertForbidden();
        $this->actingAs($user)->patch(route('workshop.work-orders.status', $order), ['lock_version' => 0, 'status' => 'in_progress'])->assertForbidden();
        $this->actingAs($user)->get(route('work-orders.print', $order))->assertForbidden();
        $this->assertSame(WorkOrderStatus::New, $order->fresh()->status);
    }

    public function test_owner_and_staff_keep_access_to_both_uis(): void
    {
        foreach ([UserRole::Owner, UserRole::Staff] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
            $this->actingAs($user)->get('/admin')->assertOk();
            $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        }
    }

    public function test_only_the_owner_reopens_a_completed_order_in_either_ui(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $order = $this->order(WorkOrderStatus::Completed);

        $this->actingAs($staff)->patch(route('workshop.work-orders.status', $order), ['lock_version' => $order->lock_version, 'status' => 'in_progress']);
        $this->assertSame(WorkOrderStatus::Completed, $order->fresh()->status);

        Livewire::actingAs($staff)->test(EditWorkOrder::class, ['record' => $order->id])
            ->fillForm(['status' => WorkOrderStatus::InProgress->value])
            ->call('save');
        $this->assertSame(WorkOrderStatus::Completed, $order->fresh()->status);

        $owner = User::factory()->create(['role' => UserRole::Owner]);
        Livewire::actingAs($owner)->test(EditWorkOrder::class, ['record' => $order->id])
            ->fillForm(['status' => WorkOrderStatus::InProgress->value])
            ->call('save');
        $this->assertSame(WorkOrderStatus::InProgress, $order->fresh()->status);
    }

    public function test_nobody_may_cancel_a_completed_order_in_either_ui(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);
        $order = $this->order(WorkOrderStatus::Completed);

        $this->assertFalse(WorkOrderStatus::Completed->canTransitionTo(WorkOrderStatus::Cancelled));

        $this->actingAs($owner)->patch(route('workshop.work-orders.status', $order), ['lock_version' => $order->lock_version, 'status' => 'cancelled'])
            ->assertSessionHasErrors('status');
        Livewire::actingAs($owner)->test(EditWorkOrder::class, ['record' => $order->id])
            ->fillForm(['status' => WorkOrderStatus::Cancelled->value])
            ->call('save');

        $this->assertSame(WorkOrderStatus::Completed, $order->fresh()->status);
    }

    public function test_purge_refuses_to_run_in_production_even_with_force(): void
    {
        $this->order(WorkOrderStatus::New);
        $this->app['env'] = 'production';

        $this->artisan('garage:purge-business-data', ['--force' => true])
            ->expectsOutputToContain('production')
            ->assertFailed();

        $this->assertSame(1, DB::table('work_orders')->count());
        $this->assertSame(1, DB::table('customers')->count());
    }

    public function test_env_example_ships_production_safe_defaults(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $example);
        $this->assertMatchesRegularExpression('/^LOG_LEVEL=warning$/m', $example);
    }
}
