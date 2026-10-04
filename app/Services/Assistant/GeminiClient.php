<?php

namespace App\Services\Assistant;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function generate(
        array $contents,
        array $declarations,
        string $stage = 'initial_request',
        ?string $toolName = null,
    ): array {
        return $this->send([
            'systemInstruction' => [
                'parts' => [['text' => $this->systemInstruction()]],
            ],
            'contents' => $contents,
            'tools' => [['functionDeclarations' => $declarations]],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 1200,
            ],
        ], $stage, $toolName);
    }

    public function generateStructured(
        array $contents,
        array $schema,
        string $systemInstruction,
        string $stage = 'structured_request',
    ): array {
        return $this->send([
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'thinkingConfig' => [
                    'thinkingLevel' => 'low',
                ],
                'maxOutputTokens' => 400,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ], $stage, timeoutSeconds: 45);
    }

    private function send(
        array $payload,
        string $stage,
        ?string $toolName = null,
        ?int $timeoutSeconds = null,
    ): array {
        $model = trim((string) config('garage-assistant.gemini.model'));
        $baseUrl = rtrim((string) config('garage-assistant.gemini.base_url'), '/');

        try {
            $response = $this->request($timeoutSeconds)->post(
                $baseUrl.'/models/'.rawurlencode($model).':generateContent',
                $payload,
            );

            $response->throw();

            return $response->json();
        } catch (RequestException $exception) {
            $response = $exception->response;
            $error = $response?->json('error', []);
            $error = is_array($error) ? $error : [];

            Log::warning('Garage assistant Gemini request rejected.', [
                'http_status' => $response?->status(),
                'gemini_code' => $error['code'] ?? null,
                'gemini_status' => $error['status'] ?? null,
                'gemini_message' => $stage === 'registration_extraction'
                    ? null
                    : $this->sanitizeErrorMessage($error['message'] ?? null),
                'model' => $model,
                'stage' => in_array($stage, ['initial_request', 'function_response_request', 'registration_extraction'], true) ? $stage : 'unknown',
                'tool' => $toolName,
            ]);

            throw $exception;
        }
    }

    private function request(?int $timeoutSeconds = null): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders(['x-goog-api-key' => (string) config('garage-assistant.gemini.api_key')])
            ->connectTimeout((int) config('garage-assistant.gemini.connect_timeout', 5))
            ->timeout($timeoutSeconds ?? (int) config('garage-assistant.gemini.timeout', 20));
    }

    private function sanitizeErrorMessage(mixed $message): ?string
    {
        if (! is_string($message) || blank($message)) {
            return null;
        }

        return str($message)
            ->replaceMatches('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/u', '[redacted-email]')
            ->replaceMatches('/(?<!\d)(?:\+30)?\d{10}(?!\d)/u', '[redacted-phone]')
            ->replaceMatches('/\b[\p{L}]{2,3}[-\s]?\d{4}\b/u', '[redacted-identifier]')
            ->replaceMatches('/\s+/u', ' ')
            ->limit(1000, '')
            ->toString();
    }

    private function systemInstruction(): string
    {
        $now = now()->format('Y-m-d H:i:s P');

        return <<<PROMPT
Είσαι ο ψηφιακός βοηθός ενός επαγγελματικού συνεργείου αυτοκινήτων.

Στόχος σου είναι να βοηθάς τον χρήστη να βρίσκει γρήγορα πληροφορίες
και να προετοιμάζει καθημερινές εργασίες του συνεργείου.

Απάντησε σύντομα, πρακτικά και καθαρά στα ελληνικά.
Τρέχουσα τοπική ημερομηνία και ώρα: {$now}.

Για δεδομένα του συνεργείου:
- Χρησιμοποίησε πάντα τα διαθέσιμα εργαλεία.
- Μην επινοείς πελάτες, οχήματα, πινακίδες, εργασίες, ημερομηνίες,
  κόστη ή αναγνωριστικά.
- Αν υπάρχουν πολλαπλά πιθανά αποτελέσματα, παρουσίασέ τα και ζήτησε
  από τον χρήστη να επιλέξει.
- Αν λείπει απαραίτητη πληροφορία, ζήτησέ την πριν προχωρήσεις.

Για ενέργειες:
- Μπορείς να προετοιμάζεις μόνο τις ενέργειες που παρέχουν τα εργαλεία.
- Μην ισχυρίζεσαι ότι έγινε αλλαγή αν δεν έχει ολοκληρωθεί από το backend.
- Η δημιουργία ραντεβού ή εντολής εργασίας απαιτεί πάντα επιβεβαίωση
  από τον χρήστη.
- Μην επιχειρείς να παρακάμψεις την επιβεβαίωση.
- Δεν διαθέτεις SQL, credentials βάσης, deletion, SMS ή άλλα εργαλεία
  πέρα από το ρητό allowlist.

Για τεχνικές ερωτήσεις αυτοκινήτου:
- Μπορείς να δώσεις γενικές τεχνικές πληροφορίες.
- Μην παρουσιάζεις πιθανή διάγνωση ως βεβαιότητα.
- Αν τα διαθέσιμα δεδομένα του οχήματος ή του ιστορικού είναι σχετικά,
  χρησιμοποίησέ τα πριν απαντήσεις.
- Ξεχώριζε καθαρά τα καταγεγραμμένα στοιχεία από την τεχνική εκτίμηση.

Μείνε προσανατολισμένος στη λειτουργία συνεργείου.
Για άσχετα θέματα, απάντησε σύντομα ότι ο ρόλος σου είναι η υποστήριξη
του συνεργείου και κατεύθυνε τη συζήτηση πίσω στη δουλειά.
PROMPT;
    }
}
