<?php

namespace Tests\Feature;

use App\Filament\Pages\AiAssistant;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Assistant\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AiAssistantToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'garage-assistant.gemini.api_key' => 'test-api-key',
            'garage-assistant.gemini.model' => 'gemini-3.6-flash',
            'garage-assistant.rate_limit.attempts' => 100,
        ]);

        Cache::flush();
        Http::preventStrayRequests();
        Http::fake();
    }

    public function test_config_reads_ai_assistant_enabled_env_and_defaults_to_false(): void
    {
        $config = file_get_contents(config_path('garage-assistant.php'));
        $example = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString("env('AI_ASSISTANT_ENABLED'", $config);
        $this->assertMatchesRegularExpression('/^AI_ASSISTANT_ENABLED=false$/m', $example);
    }

    public function test_disabled_assistant_is_hidden_from_navigation(): void
    {
        config(['garage-assistant.enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertDontSee('AI Βοηθός');

        $this->assertFalse(AiAssistant::canAccess());
    }

    public function test_enabled_assistant_is_visible_in_navigation(): void
    {
        config(['garage-assistant.enabled' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertSee('AI Βοηθός');
    }

    public function test_disabled_assistant_page_is_forbidden(): void
    {
        config(['garage-assistant.enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.ai-assistant'))
            ->assertForbidden();
    }

    public function test_disabled_assistant_livewire_actions_are_rejected_without_http(): void
    {
        config(['garage-assistant.enabled' => true]);
        $user = User::factory()->create();
        $this->actingAs($user);

        // Page mounted while enabled, then the flag is switched off before the next request.
        $component = Livewire::test(AiAssistant::class)->set('input', 'Καλημέρα');

        config(['garage-assistant.enabled' => false]);

        $component->call('sendMessage')->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_gemini_client_refuses_to_send_when_disabled(): void
    {
        config(['garage-assistant.enabled' => false]);

        try {
            app(GeminiClient::class)->generate([['role' => 'user', 'parts' => [['text' => 'x']]]], []);
            $this->fail('GeminiClient sent a request while the assistant was disabled.');
        } catch (RuntimeException) {
            // expected
        }

        Http::assertNothingSent();
    }

    public function test_gemini_structured_requests_are_refused_when_disabled(): void
    {
        config(['garage-assistant.enabled' => false]);

        try {
            app(GeminiClient::class)->generateStructured([['role' => 'user', 'parts' => [['text' => 'x']]]], ['type' => 'OBJECT'], 'sys');
            $this->fail('GeminiClient sent a structured request while the assistant was disabled.');
        } catch (RuntimeException) {
            // expected
        }

        Http::assertNothingSent();
    }

    public function test_workshop_ai_endpoints_return_404_when_disabled(): void
    {
        config(['garage-assistant.enabled' => false]);
        $this->actingAs(User::factory()->create());

        $this->get(route('workshop.assistant'))->assertNotFound();
        $this->post(route('workshop.assistant.update'), ['action' => 'send', 'message' => 'Καλημέρα'])->assertNotFound();
        $this->get(route('workshop.registration-scan.show'))->assertNotFound();
        $this->post(route('workshop.registration-scan.extract'), [
            'registration_image' => UploadedFile::fake()->image('adeia.jpg', 800, 600),
        ])->assertNotFound();
        $this->post(route('workshop.registration-scan.store'), [])->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_workshop_ai_endpoints_are_reachable_when_enabled(): void
    {
        config(['garage-assistant.enabled' => true]);
        $this->actingAs(User::factory()->create());

        $this->get(route('workshop.assistant'))->assertOk();
        $this->get(route('workshop.registration-scan.show'))->assertOk();
    }

    public function test_workshop_ui_receives_the_ai_feature_flag(): void
    {
        $this->actingAs(User::factory()->create());

        config(['garage-assistant.enabled' => false]);
        $this->get(route('workshop.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('features.ai', false));

        config(['garage-assistant.enabled' => true]);
        $this->get(route('workshop.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('features.ai', true));
    }

    public function test_full_disabled_flow_makes_no_http_call_to_google(): void
    {
        config(['garage-assistant.enabled' => false]);
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Πελάτης Δοκιμής', 'phone' => '6900000000']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΑΒΓ-1234']);

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get(route('filament.admin.pages.ai-assistant'))->assertForbidden();
        $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();

        Http::assertNothingSent();
    }
}
