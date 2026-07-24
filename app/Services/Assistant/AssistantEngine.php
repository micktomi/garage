<?php

namespace App\Services\Assistant;

use App\Models\User;
use App\Services\Assistant\DTOs\AssistantResponse;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class AssistantEngine
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly ToolRegistry $tools,
    ) {}

    public function respond(string $message, array $history, User $user, string $ipAddress): AssistantResponse
    {
        $this->authorize($user);

        if (! config('garage-assistant.enabled')) {
            return AssistantResponse::error('Ο AI Βοηθός είναι απενεργοποιημένος. Ενεργοποίησέ τον από τη ρύθμιση GARAGE_ASSISTANT_ENABLED.');
        }

        if (blank(config('garage-assistant.gemini.api_key')) || blank(config('garage-assistant.gemini.model'))) {
            return AssistantResponse::error('Λείπει η ρύθμιση Gemini. Συμπλήρωσε GEMINI_API_KEY και GEMINI_MODEL στο τοπικό .env.');
        }

        $validated = Validator::make(['message' => $message, 'history' => $history], [
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['array', 'max:12'],
            'history.*.role' => ['required', 'string', 'in:user,model'],
            'history.*.text' => ['required', 'string', 'max:5000'],
        ])->validate();

        $rateKey = 'garage-assistant:'.$user->id.':'.hash('sha256', $ipAddress);
        $allowed = RateLimiter::attempt(
            $rateKey,
            (int) config('garage-assistant.rate_limit.attempts', 20),
            fn () => true,
            (int) config('garage-assistant.rate_limit.decay_seconds', 60),
        );

        if (! $allowed) {
            return AssistantResponse::error('Έγιναν πολλά αιτήματα. Περίμενε λίγο και δοκίμασε ξανά.');
        }

        $contents = collect($validated['history'])
            ->map(fn (array $item) => ['role' => $item['role'], 'parts' => [['text' => $item['text']]]])
            ->push(['role' => 'user', 'parts' => [['text' => $validated['message']]]])
            ->values()
            ->all();

        try {
            $firstResponse = $this->gemini->generate($contents, $this->tools->declarations(), 'initial_request');
            $functionCall = $this->functionCall($firstResponse);

            if (! $functionCall) {
                return $this->directFromGemini($firstResponse);
            }

            $name = $functionCall['name'] ?? '';

            if (! is_string($name) || ! $this->tools->has($name)) {
                Log::warning('Garage assistant rejected an unknown Gemini tool name.');

                return AssistantResponse::error('Ο βοηθός ζήτησε μη επιτρεπτή ενέργεια. Δεν εκτελέστηκε τίποτα.');
            }

            $arguments = is_array($functionCall['args'] ?? null) ? $functionCall['args'] : [];
            $toolResult = $this->tools->execute($name, $arguments, $user);

            if ($toolResult->terminalResponse) {
                return $toolResult->terminalResponse;
            }

            $modelContent = data_get($firstResponse, 'candidates.0.content');

            if (! is_array($modelContent)) {
                return AssistantResponse::error('Δεν ήταν δυνατή η επεξεργασία της απάντησης του βοηθού.');
            }

            $modelContent = $this->normalizeFunctionCallArgsForReplay($modelContent);

            $contents[] = $modelContent;
            $contents[] = [
                'role' => 'user',
                'parts' => [[
                    'functionResponse' => [
                        'name' => $name,
                        'response' => $toolResult->forGemini(),
                    ],
                ]],
            ];

            return $this->directFromGemini($this->gemini->generate($contents, $this->tools->declarations(), 'function_response_request', $name), $toolResult->data);
        } catch (ValidationException $exception) {
            return AssistantResponse::error(collect($exception->errors())->flatten()->first() ?: 'Τα στοιχεία του αιτήματος δεν είναι έγκυρα.');
        } catch (RequestException) {
            return AssistantResponse::error('Ο AI Βοηθός δεν είναι προσωρινά διαθέσιμος. Δεν εκτελέστηκε καμία αλλαγή.');
        } catch (ConnectionException $exception) {
            Log::warning('Garage assistant Gemini connection failed.', ['exception' => $exception::class]);

            return AssistantResponse::error('Το Gemini δεν απάντησε εγκαίρως. Δοκίμασε ξανά σε λίγο.');
        } catch (Throwable $exception) {
            Log::warning('Garage assistant request failed.', ['exception' => $exception::class]);

            return AssistantResponse::error('Ο AI Βοηθός δεν είναι προσωρινά διαθέσιμος. Δεν εκτελέστηκε καμία αλλαγή.');
        }
    }

    private function authorize(User $user): void
    {
        if (! $user->canAccessPanel(Filament::getPanel('admin'))) {
            throw new AuthorizationException;
        }
    }

    private function functionCall(array $response): ?array
    {
        foreach ((array) data_get($response, 'candidates.0.content.parts', []) as $part) {
            if (is_array($part['functionCall'] ?? null)) {
                return $part['functionCall'];
            }
        }

        return null;
    }

    private function normalizeFunctionCallArgsForReplay(array $content): array
    {
        foreach ($content['parts'] ?? [] as $index => $part) {
            $args = $part['functionCall']['args'] ?? null;

            if (is_array($args)) {
                $content['parts'][$index]['functionCall']['args'] = (object) $args;
            }
        }

        return $content;
    }

    private function directFromGemini(array $response, array $data = []): AssistantResponse
    {
        $parts = (array) data_get($response, 'candidates.0.content.parts', []);
        $text = collect($parts)
            ->pluck('text')
            ->filter(fn ($value) => is_string($value) && filled($value))
            ->implode("\n");

        if (blank($text)) {
            return AssistantResponse::error('Το Gemini επέστρεψε κενή ή μη έγκυρη απάντηση.');
        }

        return AssistantResponse::direct($text, $data);
    }
}
