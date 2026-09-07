<?php

namespace Tests\Feature\Services\Assistant;

use App\Services\Assistant\VehicleRegistrationExtractor;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VehicleRegistrationExtractorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'garage-assistant.enabled' => true,
            'garage-assistant.gemini.api_key' => 'test-api-key',
            'garage-assistant.gemini.model' => 'gemini-3.7-flash',
        ]);

        Http::preventStrayRequests();
    }

    public function test_it_sends_the_image_to_the_existing_gemini_client_with_a_response_schema(): void
    {
        Http::fake(['*' => Http::response($this->geminiJsonResponse([
            'owner_full_name' => 'Μαρία Ιωάννου',
            'afm' => '123456789',
            'phone' => null,
            'address' => 'Αθήνα',
            'plate_number' => 'ΑΒΓ-1234',
            'vin' => 'WVWZZZ1JZXW000001',
            'make' => 'Volkswagen',
            'model' => 'Golf',
            'fuel' => 'Βενζίνη',
            'engine_cc' => 1390,
            'first_registered_at' => '2018-06-15',
        ]))]);

        $extractor = app(VehicleRegistrationExtractor::class);
        $result = $extractor->extract(UploadedFile::fake()->image('adeia.jpg', 1200, 800));

        $this->assertSame('ΑΒΓ-1234', $result['plate_number']);
        $this->assertSame('WVWZZZ1JZXW000001', $result['vin']);
        $this->assertSame(1390, $result['engine_cc']);
        $this->assertSame('2018-06-15', $result['first_registered_at']);
        $this->assertArrayHasKey('gemini_ms', $extractor->timings());

        Http::assertSent(function ($request): bool {
            $payload = json_decode($request->body(), true);

            return str_contains($request->url(), '/models/gemini-3.7-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-api-key')
                && data_get($payload, 'generationConfig.thinkingConfig.thinkingLevel') === 'low'
                && data_get($payload, 'generationConfig.maxOutputTokens') === 400
                && data_get($payload, 'generationConfig.responseMimeType') === 'application/json'
                && data_get($payload, 'generationConfig.responseSchema.type') === 'OBJECT'
                && ! array_key_exists('thinkingBudget', data_get($payload, 'generationConfig.thinkingConfig', []))
                && ! array_key_exists('thinking_budget', data_get($payload, 'generationConfig.thinkingConfig', []))
                && ! array_key_exists('temperature', data_get($payload, 'generationConfig', []))
                && ! array_key_exists('topP', data_get($payload, 'generationConfig', []))
                && ! array_key_exists('topK', data_get($payload, 'generationConfig', []))
                && ! array_key_exists('top_p', data_get($payload, 'generationConfig', []))
                && ! array_key_exists('top_k', data_get($payload, 'generationConfig', []))
                && str_contains((string) data_get($payload, 'systemInstruction.parts.0.text'), 'πεδίο (E)')
                && str_contains((string) data_get($payload, 'systemInstruction.parts.0.text'), 'Μην αντικαθιστάς')
                && str_contains((string) data_get($payload, 'systemInstruction.parts.0.text'), 'OCR/μεταγραφή')
                && str_contains((string) data_get($payload, 'systemInstruction.parts.0.text'), 'δεν κάνεις ιατρική ή μηχανική διάγνωση')
                && data_get($payload, 'contents.0.parts.1.inlineData.mimeType') === 'image/jpeg'
                && filled(data_get($payload, 'contents.0.parts.1.inlineData.data'))
                && ! array_key_exists('tools', $payload);
        });
    }

    public function test_it_rejects_a_non_json_gemini_response(): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => 'not-json']]],
            ]],
        ])]);

        $this->expectException(ValidationException::class);

        app(VehicleRegistrationExtractor::class)
            ->extract(UploadedFile::fake()->image('adeia.jpg'));
    }

    public function test_it_rejects_prompt_feedback_blocks_before_parsing(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response([
            'promptFeedback' => ['blockReason' => 'SAFETY'],
        ])]);

        try {
            app(VehicleRegistrationExtractor::class)
                ->extract(UploadedFile::fake()->image('adeia.jpg'));
            $this->fail('Expected blocked prompt to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('αποκλείστηκε', $exception->errors()['registration_image'][0]);
        }

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $message === 'Vehicle registration extraction rejected Gemini response.'
                && $context['source'] === 'prompt_feedback'
                && $context['reason'] === 'SAFETY'
                && $context['stage'] === 'registration_extraction'
        )->once();
    }

    #[DataProvider('nonStopFinishReasons')]
    public function test_it_rejects_non_stop_finish_reasons_before_parsing(string $finishReason, string $expectedMessage): void
    {
        $refusal = 'Δεν μπορώ να κάνω διάγνωση.';
        Log::spy();
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'finishReason' => $finishReason,
                'content' => ['parts' => [['text' => $refusal]]],
            ]],
        ])]);

        try {
            app(VehicleRegistrationExtractor::class)
                ->extract(UploadedFile::fake()->image('adeia.jpg'));
            $this->fail('Expected non-STOP response to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($expectedMessage, $exception->errors()['registration_image'][0]);
        }

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $message === 'Vehicle registration extraction rejected Gemini response.'
                && $context['source'] === 'candidate'
                && $context['reason'] === $finishReason
                && ! str_contains(json_encode($context, JSON_UNESCAPED_UNICODE), $refusal)
        )->once();
    }

    public static function nonStopFinishReasons(): array
    {
        return [
            'SPII' => ['SPII', 'προστασίας προσωπικών δεδομένων'],
            'SAFETY' => ['SAFETY', 'αποκλείστηκε'],
            'MAX_TOKENS' => ['MAX_TOKENS', 'δεν ολοκληρώθηκε'],
            'MALFORMED_RESPONSE' => ['MALFORMED_RESPONSE', 'μη έγκυρη δομημένη απάντηση'],
        ];
    }

    public function test_it_preserves_each_real_gemini_vin_result_without_character_substitution(): void
    {
        $vins = [
            'VF3LRYHZPJS396912',
            'VF3LBYHZPJS396912',
            'VF3LBYHZRJS396912',
        ];
        $responses = Http::sequence();

        foreach ($vins as $vin) {
            $responses->push($this->geminiJsonResponse([
                'owner_full_name' => null,
                'afm' => null,
                'phone' => null,
                'address' => null,
                'plate_number' => 'ΙΥΖ-5020',
                'vin' => $vin,
                'make' => null,
                'model' => null,
                'fuel' => null,
                'engine_cc' => null,
                'first_registered_at' => null,
            ]));
        }

        Http::fake(['*' => $responses]);

        foreach ($vins as $vin) {
            $extractor = app(VehicleRegistrationExtractor::class);
            $result = $extractor->extract(UploadedFile::fake()->image('adeia.jpg'));

            $this->assertSame($vin, $result['vin']);
            $this->assertGreaterThanOrEqual(0, $extractor->timings()['gemini_ms']);
            $this->assertGreaterThanOrEqual(0, $extractor->timings()['decode_validation_ms']);
        }
    }

    public function test_it_keeps_other_fields_when_an_extracted_vin_is_invalid_or_uncertain(): void
    {
        Http::fake(['*' => Http::response($this->geminiJsonResponse([
            'owner_full_name' => null,
            'afm' => null,
            'phone' => null,
            'address' => null,
            'plate_number' => 'ΙΥΖ-5020',
            'vin' => 'VF3LRYHZPIS396912',
            'make' => null,
            'model' => null,
            'fuel' => null,
            'engine_cc' => null,
            'first_registered_at' => null,
        ]))]);

        $result = app(VehicleRegistrationExtractor::class)
            ->extract(UploadedFile::fake()->image('adeia.jpg'));

        $this->assertSame('ΙΥΖ-5020', $result['plate_number']);
        $this->assertNull($result['vin']);
    }

    public function test_registration_extraction_errors_do_not_log_gemini_pii(): void
    {
        $vin = 'VF3LRYHZPJS396912';
        Log::spy();
        Http::fake(['*' => Http::response([
            'error' => [
                'code' => 400,
                'status' => 'INVALID_ARGUMENT',
                'message' => 'Rejected extracted VIN '.$vin,
            ],
        ], 400)]);

        try {
            app(VehicleRegistrationExtractor::class)
                ->extract(UploadedFile::fake()->image('adeia.jpg'));
            $this->fail('Expected Gemini request failure.');
        } catch (RequestException) {
            // Assert the sanitized production log context below.
        }

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $message === 'Garage assistant Gemini request rejected.'
                && $context['gemini_message'] === null
                && ! str_contains(json_encode($context), $vin)
        )->once();
    }

    private function geminiJsonResponse(array $data): array
    {
        return [
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => [
                    'role' => 'model',
                    'parts' => [['text' => json_encode($data, JSON_UNESCAPED_UNICODE)]],
                ],
            ]],
        ];
    }
}
