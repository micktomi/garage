<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkshopAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'garage-assistant.enabled' => true,
            'garage-assistant.gemini.api_key' => 'test-api-key',
            'garage-assistant.gemini.model' => 'test-model',
        ]);
        Http::preventStrayRequests();
    }

    public function test_missing_model_disables_the_composer_and_returns_a_safe_error(): void
    {
        config(['garage-assistant.gemini.model' => '']);
        $this->actingAs(User::factory()->create())
            ->get('/workshop/assistant')
            ->assertInertia(fn (Assert $page) => $page->where('enabled', false));
        $this->post('/workshop/assistant', ['action' => 'send', 'message' => 'Καλημέρα'])
            ->assertRedirect('/workshop/assistant');
        $this->get('/workshop/assistant')->assertInertia(fn (Assert $page) => $page
            ->where('messages.1.type', 'error')
            ->where('messages.1.message', fn ($message) => str_contains($message, 'Λείπει η ρύθμιση Gemini')));
        Http::assertNothingSent();
    }

    public function test_the_react_endpoint_uses_gemini_tools_and_keeps_conversation_history(): void
    {
        $customer = Customer::create(['full_name' => 'Δοκιμαστικός Πελάτης']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'TEST1234', 'make' => 'Toyota']);
        Http::fakeSequence()
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [[
                'functionCall' => ['name' => 'find_vehicle', 'args' => ['query' => 'TEST1234']],
                'thoughtSignature' => 'test-signature',
            ]]]]]])
            ->push($this->textResponse('Βρέθηκε το Toyota TEST1234.'))
            ->push($this->textResponse('Μπορώ να αναζητήσω το ιστορικό του.'));
        $this->actingAs(User::factory()->create());
        $this->post('/workshop/assistant', ['action' => 'send', 'message' => 'Βρες το TEST1234'])
            ->assertSessionHasNoErrors()->assertRedirect('/workshop/assistant');
        $this->get('/workshop/assistant')->assertInertia(fn (Assert $page) => $page
            ->where('messages.1.data.vehicle.plate_number', 'TEST1234')
            ->where('proposal', null));
        $this->post('/workshop/assistant', ['action' => 'send', 'message' => 'Τι άλλο μπορείς να βρεις;'])
            ->assertSessionHasNoErrors();
        $requests = Http::recorded();
        $this->assertSame('test-signature', $requests[1][0]['contents'][1]['parts'][0]['thoughtSignature']);
        $this->assertSame('Βρες το TEST1234', $requests[2][0]['contents'][0]['parts'][0]['text']);
        $this->assertSame('Βρέθηκε το Toyota TEST1234.', $requests[2][0]['contents'][1]['parts'][0]['text']);
        Http::assertSentCount(3);
        $this->assertDatabaseCount('work_orders', 0);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_connection_failures_reach_the_react_chat_as_safe_error_messages(): void
    {
        Http::fake(['*' => Http::failedConnection('private transport detail')]);
        $this->actingAs(User::factory()->create())
            ->post('/workshop/assistant', ['action' => 'send', 'message' => 'Ποια ραντεβού έχουμε;'])
            ->assertRedirect('/workshop/assistant')->assertSessionHasNoErrors();
        $this->get('/workshop/assistant')->assertInertia(fn (Assert $page) => $page
            ->where('messages.1.type', 'error')
            ->where('messages.1.message', fn ($message) => str_contains($message, 'δεν απάντησε εγκαίρως') && ! str_contains($message, 'private transport detail')));
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_history_is_scoped_to_the_signed_in_user_and_clear_removes_it(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        Http::fake(['*' => Http::response($this->textResponse('Καλημέρα!'))]);
        $this->actingAs($first)->post('/workshop/assistant', ['action' => 'send', 'message' => 'Καλημέρα']);
        $this->actingAs($second)->get('/workshop/assistant')
            ->assertInertia(fn (Assert $page) => $page->has('messages', 0));
        $this->actingAs($first)->get('/workshop/assistant')
            ->assertInertia(fn (Assert $page) => $page->has('messages', 2));
        $this->post('/workshop/assistant', ['action' => 'clear'])->assertRedirect('/workshop/assistant');
        $this->get('/workshop/assistant')->assertInertia(fn (Assert $page) => $page->has('messages', 0));
    }

    private function textResponse(string $text): array
    {
        return ['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => $text]]]]]];
    }
}
