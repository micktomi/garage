<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Users are the one record type staff must never touch: anybody who can
 * create or edit users can promote themselves to owner and undo every
 * other restriction in the app. The whole resource is owner-only.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_owner_may_open_the_users_resource(): void
    {
        $this->assertTrue(Gate::forUser($this->owner())->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($this->staff())->allows('viewAny', User::class));

        $this->actingAs($this->owner());
        $this->assertTrue(UserResource::canViewAny());
        $this->get(UserResource::getUrl('index'))->assertOk();

        $this->actingAs($this->staff());
        $this->assertFalse(UserResource::canViewAny());
        $this->get(UserResource::getUrl('index'))->assertForbidden();
    }

    public function test_only_the_owner_may_create_a_user(): void
    {
        $this->actingAs($this->staff())->get(UserResource::getUrl('create'))->assertForbidden();

        Livewire::actingAs($this->owner())
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Νέο Προσωπικό',
                'email' => 'staff2@example.com',
                'role' => UserRole::Staff->value,
                'password' => 'secret-password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'staff2@example.com')->firstOrFail();
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_only_the_owner_may_delete_a_user(): void
    {
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($this->owner())->allows('delete', $target));
        $this->assertFalse(Gate::forUser($this->staff())->allows('delete', $target));
    }

    private function owner(): User
    {
        return User::factory()->create(['role' => UserRole::Owner]);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => UserRole::Staff]);
    }
}
