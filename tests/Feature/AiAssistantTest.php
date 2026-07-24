<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Assistant\AssistantEngine;
use App\Services\Assistant\ProposalExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'garage-assistant.enabled' => true,
            'garage-assistant.gemini.api_key' => 'test-api-key',
            'garage-assistant.gemini.model' => 'gemini-3.6-flash',
            'garage-assistant.rate_limit.attempts' => 100,
        ]);

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_unauthorized_user_cannot_access_assistant_page(): void
    {
        $this->get(route('filament.admin.pages.ai-assistant'))
            ->assertRedirect('/admin/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('filament.admin.pages.ai-assistant'))
            ->assertOk()
            ->assertSee('Μήνυμα ή transcript');
    }

    public function test_feature_disabled_returns_safe_error_without_calling_gemini(): void
    {
        config(['garage-assistant.enabled' => false]);
        $user = User::factory()->create();

        $response = $this->respondAs($user, 'Καλημέρα');

        $this->assertSame('error', $response->type);
        $this->actingAs($user)->get(route('filament.admin.pages.ai-assistant'))
            ->assertOk()
            ->assertSee('Ο AI Βοηθός είναι απενεργοποιημένος');
        $this->assertStringContainsString('απενεργοποιημένος', $response->message);
        Http::assertNothingSent();
    }

    public function test_missing_gemini_configuration_returns_safe_error(): void
    {
        config(['garage-assistant.gemini.api_key' => null]);
        $user = User::factory()->create();

        $response = $this->respondAs($user, 'Ποια ραντεβού έχουμε;');

        $this->assertSame('error', $response->type);
        $this->actingAs($user)->get(route('filament.admin.pages.ai-assistant'))
            ->assertOk()
            ->assertSee('Λείπει το Gemini API key');
        $this->assertStringContainsString('Λείπει η ρύθμιση Gemini', $response->message);
        Http::assertNothingSent();
    }

    public function test_read_only_request_flow_uses_mocked_gemini_tool_call(): void
    {
        [$user, $customer, $vehicle] = $this->makeCustomerAndVehicle('ΑΒΓ-1234');
        Http::fakeSequence()
            ->push($this->functionCall('find_vehicle', ['query' => 'ΑΒΓ-1234']))
            ->push($this->textResponse('Βρέθηκε το Toyota Yaris με πινακίδα ΑΒΓ-1234.'));

        $response = $this->respondAs($user, 'Βρες το όχημα με πινακίδα ΑΒΓ-1234.');

        $this->assertSame('direct_answer', $response->type);
        $this->assertSame('ΑΒΓ-1234', $response->data['vehicle']['plate_number']);
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('work_orders', 0);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-api-key')
            && str_contains($request->url(), '/models/gemini-3.6-flash:generateContent'));
    }

    public function test_ambiguous_vehicle_and_customer_results_require_selection(): void
    {
        $user = User::factory()->create();
        $firstCustomer = Customer::create(['full_name' => 'Νίκος Παπαδόπουλος', 'phone' => '6900000001']);
        $secondCustomer = Customer::create(['full_name' => 'Νίκος Παπαϊωάννου', 'phone' => '6900000002']);
        Vehicle::create(['customer_id' => $firstCustomer->id, 'plate_number' => 'ΑΒΓ-1234']);
        Vehicle::create(['customer_id' => $secondCustomer->id, 'plate_number' => 'ΔΕΖ-1234']);
        Http::fakeSequence()
            ->push($this->functionCall('find_vehicle', ['query' => '1234']))
            ->push($this->functionCall('find_customer', ['query' => 'Νίκος']));

        $vehicleResponse = $this->respondAs($user, 'Βρες το όχημα που τελειώνει σε 1234.');
        $customerResponse = $this->respondAs($user, 'Βρες τον Νίκο.');

        $this->assertSame('ambiguity', $vehicleResponse->type);
        $this->assertCount(2, $vehicleResponse->data['options']);
        $this->assertSame('ambiguity', $customerResponse->type);
        $this->assertCount(2, $customerResponse->data['options']);
        Http::assertSentCount(2);
    }

    public function test_not_found_vehicle_returns_clean_direct_answer(): void
    {
        $user = User::factory()->create();
        Http::fakeSequence()->push($this->functionCall('find_vehicle', ['query' => 'ΑΝΥ-0000']));

        $response = $this->respondAs($user, 'Βρες το ΑΝΥ-0000.');

        $this->assertSame('direct_answer', $response->type);
        $this->assertSame('not_found', $response->data['status']);
        $this->assertStringContainsString('Δεν βρέθηκε', $response->message);
    }

    public function test_prepare_appointment_returns_proposal_without_database_write(): void
    {
        [$user] = $this->makeCustomerAndVehicle('ΡΑΝ-1000');
        Http::fakeSequence()->push($this->functionCall('prepare_appointment', [
            'plate_number' => 'ΡΑΝ-1000',
            'appointment_at' => now()->addDay()->setTime(10, 30)->toIso8601String(),
            'description' => 'Έλεγχος φρένων',
        ]));

        $response = $this->respondAs($user, 'Κλείσε ραντεβού αύριο στις 10:30 για το ΡΑΝ-1000.');

        $this->assertSame('confirmation_required', $response->type);
        $this->assertSame('create_appointment', $response->proposal['action_type']);
        $this->assertTrue($response->proposal['requires_confirmation']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_prepare_work_order_returns_proposal_without_database_write(): void
    {
        [$user] = $this->makeCustomerAndVehicle('ΕΝΤ-2000');
        Http::fakeSequence()->push($this->functionCall('prepare_work_order', [
            'plate_number' => 'ΕΝΤ-2000',
            'problem_description' => 'Θόρυβος στα φρένα',
            'current_mileage' => 85000,
        ]));

        $response = $this->respondAs($user, 'Ετοίμασε εντολή εργασίας για το ΕΝΤ-2000.');

        $this->assertSame('confirmation_required', $response->type);
        $this->assertSame('create_work_order', $response->proposal['action_type']);
        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_confirmed_appointment_is_created_deterministically(): void
    {
        [$user, $customer, $vehicle] = $this->makeCustomerAndVehicle('ΡΑΝ-3000');
        Http::fakeSequence()->push($this->functionCall('prepare_appointment', [
            'plate_number' => 'ΡΑΝ-3000',
            'appointment_at' => now()->addDay()->setTime(11, 0)->toIso8601String(),
            'description' => 'Service',
        ]));
        $proposal = $this->respondAs($user, 'Προετοίμασε ραντεβού.');

        $result = app(ProposalExecutor::class)->execute($user, $proposal->proposal['token']);

        $this->assertSame('direct_answer', $result->type);
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'description' => 'Service',
            'status' => 'scheduled',
        ]);
    }

    public function test_confirmed_work_order_is_created_deterministically(): void
    {
        [$user, $customer, $vehicle] = $this->makeCustomerAndVehicle('ΕΝΤ-4000');
        Http::fakeSequence()->push($this->functionCall('prepare_work_order', [
            'plate_number' => 'ΕΝΤ-4000',
            'problem_description' => 'Αλλαγή λαδιών',
            'current_mileage' => 90000,
        ]));
        $proposal = $this->respondAs($user, 'Προετοίμασε εντολή εργασίας.');

        $result = app(ProposalExecutor::class)->execute($user, $proposal->proposal['token']);

        $this->assertSame('direct_answer', $result->type);
        $this->assertDatabaseHas('work_orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αλλαγή λαδιών',
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'mileage' => 90000]);
    }

    public function test_tampered_confirmation_token_is_rejected_without_write(): void
    {
        [$user] = $this->makeCustomerAndVehicle('ΡΑΝ-5000');
        Http::fakeSequence()->push($this->functionCall('prepare_appointment', [
            'plate_number' => 'ΡΑΝ-5000',
            'appointment_at' => now()->addDay()->setTime(12, 0)->toIso8601String(),
        ]));
        $proposal = $this->respondAs($user, 'Προετοίμασε ραντεβού.');
        $token = $proposal->proposal['token'];
        $tamperedToken = substr($token, 0, -1).($token[-1] === '0' ? '1' : '0');

        try {
            app(ProposalExecutor::class)->execute($user, $tamperedToken);
            $this->fail('Expected validation failure for tampered proposal token.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('proposal', $exception->errors());
        }

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_empty_function_call_args_are_replayed_as_json_object(): void
    {
        $user = User::factory()->create();
        Http::fakeSequence()
            ->push($this->functionCall('list_open_work_orders', []))
            ->push($this->textResponse('Δεν υπάρχουν ανοιχτές εντολές εργασίας.'));

        $response = $this->respondAs($user, 'Ποιες εντολές εργασίας είναι ανοιχτές;');
        $recorded = Http::recorded();
        $secondPayload = json_decode($recorded[1][0]->body());
        $replayedArgs = $secondPayload->contents[1]->parts[0]->functionCall->args;

        $this->assertSame('direct_answer', $response->type);
        $this->assertIsObject($replayedArgs);
        $this->assertSame([], get_object_vars($replayedArgs));
    }

    public function test_function_response_rejection_logs_safe_gemini_diagnostics(): void
    {
        $user = User::factory()->create();
        Log::spy();
        Http::fakeSequence()
            ->push($this->functionCall('list_open_work_orders', []))
            ->push([
                'error' => [
                    'code' => 400,
                    'status' => 'INVALID_ARGUMENT',
                    'message' => 'Invalid function response for ΑΒΓ-1234, owner@example.com, 6912345678.',
                ],
            ], 400);

        $response = $this->respondAs($user, 'Ποιες εντολές εργασίας είναι ανοιχτές;');

        $this->assertSame('error', $response->type);
        $this->assertStringNotContainsString('INVALID_ARGUMENT', $response->message);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Garage assistant Gemini request rejected.', \Mockery::on(function (array $context): bool {
                $encoded = json_encode($context, JSON_UNESCAPED_UNICODE);

                return $context['http_status'] === 400
                    && $context['gemini_code'] === 400
                    && $context['gemini_status'] === 'INVALID_ARGUMENT'
                    && $context['gemini_message'] === 'Invalid function response for [redacted-identifier], [redacted-email], [redacted-phone].'
                    && $context['model'] === 'gemini-3.6-flash'
                    && $context['stage'] === 'function_response_request'
                    && $context['tool'] === 'list_open_work_orders'
                    && ! array_key_exists('headers', $context)
                    && ! array_key_exists('prompt', $context)
                    && ! array_key_exists('tool_result', $context)
                    && ! str_contains($encoded, 'test-api-key')
                    && ! str_contains($encoded, 'owner@example.com')
                    && ! str_contains($encoded, '6912345678');
            }));
    }

    public function test_gemini_timeout_is_handled_without_exposing_raw_error(): void
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::failedConnection('secret transport detail')]);

        $response = $this->respondAs($user, 'Ποια ραντεβού έχουμε αύριο;');

        $this->assertSame('error', $response->type);
        $this->assertStringContainsString('δεν απάντησε εγκαίρως', $response->message);
        $this->assertStringNotContainsString('secret transport detail', $response->message);
    }

    private function respondAs(User $user, string $message)
    {
        return app(AssistantEngine::class)->respond($message, [], $user, '127.0.0.1');
    }

    private function makeCustomerAndVehicle(string $plate): array
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'full_name' => 'Δοκιμαστικός Πελάτης',
            'phone' => '6900000000',
        ]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Toyota',
            'model' => 'Yaris',
            'mileage' => 80000,
        ]);

        return [$user, $customer, $vehicle];
    }

    private function functionCall(string $name, array $args): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'role' => 'model',
                    'parts' => [['functionCall' => ['name' => $name, 'args' => $args]]],
                ],
            ]],
        ];
    }

    private function textResponse(string $text): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'role' => 'model',
                    'parts' => [['text' => $text]],
                ],
            ]],
        ];
    }
}
