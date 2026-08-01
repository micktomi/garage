<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The app has no route named 'login', only Filament's admin panel login, so
 * bootstrap/app.php resolves the guest destination itself.
 */
class GuestRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_html_request_is_sent_to_the_filament_login(): void
    {
        $this->get(route('workshop.dashboard'))->assertRedirect('/admin/login');
    }

    public function test_every_protected_workshop_route_redirects_guests_to_the_filament_login(): void
    {
        $routes = [
            route('workshop.work-orders.index'),
            route('workshop.work-orders.create'),
            route('workshop.customers.index'),
            route('workshop.appointments.index'),
            route('workshop.search'),
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/admin/login');
        }
    }

    public function test_unauthenticated_json_request_is_rejected_with_401(): void
    {
        $this->getJson(route('workshop.dashboard'))->assertUnauthorized();

        $this->postJson(route('workshop.work-orders.store'), [])->assertUnauthorized();
    }

    public function test_authenticated_request_is_not_redirected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
    }
}
