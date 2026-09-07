<?php

namespace App\Services\Assistant;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

final class VehicleRegistrationExtractor
{
    private const FIELDS = [
        'owner_full_name',
        'afm',
        'phone',
        'address',
        'plate_number',
        'vin',
        'make',
        'model',
        'fuel',
        'engine_cc',
        'first_registered_at',
    ];

    /** @var array{gemini_ms: float, decode_validation_ms: float} */
    private array $timings = [
        'gemini_ms' => 0.0,
        'decode_validation_ms' => 0.0,
    ];

    public function __construct(private readonly GeminiClient $gemini) {}

    public function extract(UploadedFile $image): array
    {
        $this->timings = [
            'gemini_ms' => 0.0,
            'decode_validation_ms' => 0.0,
        ];

        if (! config('garage-assistant.enabled')) {
            throw ValidationException::withMessages([
                'registration_image' => 'Η αναγνώριση άδειας είναι προσωρινά απενεργοποιημένη.',
            ]);
        }

        if (blank(config('garage-assistant.gemini.api_key')) || blank(config('garage-assistant.gemini.model'))) {
            throw ValidationException::withMessages([
                'registration_image' => 'Λείπει η ρύθμιση Gemini για την αναγνώριση άδειας.',
            ]);
        }

        $mimeType = (string) $image->getMimeType();
        $bytes = $image->get();

        if ($bytes === false || $bytes === '') {
            throw ValidationException::withMessages([
                'registration_image' => 'Η εικόνα δεν μπόρεσε να διαβαστεί.',
            ]);
        }

        $geminiStartedAt = hrtime(true);

        try {
            $response = $this->gemini->generateStructured(
                [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Διάβασε την ελληνική άδεια κυκλοφορίας και επέστρεψε αποκλειστικά τα πεδία του schema. Το VIN επιτρέπεται να προέλθει μόνο από το πεδίο (E).'],
                        ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode($bytes)]],
                    ],
                ]],
                $this->schema(),
                $this->systemInstruction(),
                'registration_extraction',
            );
        } finally {
            $this->timings['gemini_ms'] = $this->elapsedMilliseconds($geminiStartedAt);
        }

        $this->assertResponseCanBeParsed($response);

        $decodeStartedAt = hrtime(true);

        $text = collect((array) data_get($response, 'candidates.0.content.parts', []))
            ->pluck('text')
            ->filter(fn ($value) => is_string($value) && filled($value))
            ->implode("\n");

        if (blank($text)) {
            throw ValidationException::withMessages([
                'registration_image' => 'Δεν αναγνωρίστηκαν στοιχεία από την εικόνα.',
            ]);
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'registration_image' => 'Η αναγνώριση επέστρεψε μη έγκυρα δομημένα δεδομένα.',
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'registration_image' => 'Η αναγνώριση δεν επέστρεψε έγκυρα στοιχεία.',
            ]);
        }

        $data = array_fill_keys(self::FIELDS, null);

        foreach (self::FIELDS as $field) {
            $value = $decoded[$field] ?? null;
            $data[$field] = is_string($value) ? trim($value) : $value;
            $data[$field] = $data[$field] === '' ? null : $data[$field];
        }

        $data['engine_cc'] = $this->integerOrNull($data['engine_cc']);
        $data['first_registered_at'] = $this->dateOrNull($data['first_registered_at']);
        $data['vin'] = $this->validVinOrNull($data['vin']);

        Validator::make($data, [
            'owner_full_name' => ['nullable', 'string', 'max:255'],
            'afm' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'fuel' => ['nullable', 'string', 'max:50'],
            'engine_cc' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'first_registered_at' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();

        if (collect($data)->filter(fn ($value) => filled($value))->isEmpty()) {
            throw ValidationException::withMessages([
                'registration_image' => 'Δεν βρέθηκαν αναγνώσιμα στοιχεία άδειας.',
            ]);
        }

        $this->timings['decode_validation_ms'] = $this->elapsedMilliseconds($decodeStartedAt);

        return $data;
    }

    /** @return array{gemini_ms: float, decode_validation_ms: float} */
    public function timings(): array
    {
        return $this->timings;
    }

    private function schema(): array
    {
        $string = static fn (string $description): array => [
            'type' => 'STRING',
            'nullable' => true,
            'description' => $description,
        ];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'owner_full_name' => $string('Ονοματεπώνυμο ή επωνυμία κατόχου.'),
                'afm' => $string('Ελληνικό ΑΦΜ μόνο αν εμφανίζεται καθαρά.'),
                'phone' => $string('Τηλέφωνο μόνο αν εμφανίζεται· συνήθως null.'),
                'address' => $string('Διεύθυνση κατόχου μόνο αν εμφανίζεται.'),
                'plate_number' => $string('Αριθμός κυκλοφορίας.'),
                'vin' => $string('Ακριβώς 17 χαρακτήρες VIN, αποκλειστικά από το πεδίο (E). Αν έστω ένας χαρακτήρας είναι αβέβαιος, null.'),
                'make' => $string('Μάρκα οχήματος.'),
                'model' => $string('Μοντέλο ή εμπορική ονομασία.'),
                'fuel' => $string('Καύσιμο σε σύντομη ελληνική περιγραφή.'),
                'engine_cc' => [
                    'type' => 'INTEGER',
                    'nullable' => true,
                    'description' => 'Κυβισμός σε κυβικά εκατοστά.',
                ],
                'first_registered_at' => $string('Ημερομηνία πρώτης κυκλοφορίας σε YYYY-MM-DD.'),
            ],
            'required' => self::FIELDS,
        ];
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
Εξάγεις στοιχεία αποκλειστικά από φωτογραφία ελληνικής άδειας κυκλοφορίας.
Η εργασία είναι αποκλειστικά OCR/μεταγραφή εγγράφου που ανέβασε ο χρήστης για καταχώριση· δεν κάνεις ιατρική ή μηχανική διάγνωση και δεν δίνεις συμβουλές.
Αν η εικόνα δεν είναι ελληνική άδεια κυκλοφορίας ή δεν διαβάζεται, επέστρεψε όλα τα πεδία του schema ως null.
Μην μαντεύεις και μην συμπληρώνεις πληροφορίες από γενικές γνώσεις. Για δυσανάγνωστο ή απόν πεδίο επέστρεψε null.
Μην χρησιμοποιείς μάρκα, μοντέλο, κατασκευαστή ή άλλα πεδία για να συμπεράνεις ή να διορθώσεις κανένα στοιχείο.
Διάβασε το VIN αποκλειστικά και κατά γράμμα από το πεδίο (E). Μην αντικαθιστάς οπτικά παρόμοιους χαρακτήρες. Αν έστω ένας χαρακτήρας είναι αβέβαιος, επέστρεψε vin: null.
Διατήρησε ακριβώς τα γράμματα και ψηφία πινακίδας, VIN και ΑΦΜ όπως φαίνονται.
Η απάντηση πρέπει να συμμορφώνεται ακριβώς με το δοσμένο JSON schema.
PROMPT;
    }

    private function assertResponseCanBeParsed(array $response): void
    {
        $blockReason = data_get($response, 'promptFeedback.blockReason');

        if (is_string($blockReason) && filled($blockReason)) {
            $this->rejectResponse('prompt_feedback', $blockReason);
        }

        $finishReason = data_get($response, 'candidates.0.finishReason');

        if ($finishReason !== 'STOP') {
            $this->rejectResponse('candidate', $finishReason);
        }
    }

    private function rejectResponse(string $source, mixed $reason): never
    {
        $safeReason = is_string($reason) && in_array($reason, [
            'SAFETY',
            'SPII',
            'MAX_TOKENS',
            'MALFORMED_RESPONSE',
            'PROHIBITED_CONTENT',
            'BLOCKLIST',
            'IMAGE_SAFETY',
            'OTHER',
        ], true) ? $reason : 'UNKNOWN';

        Log::warning('Vehicle registration extraction rejected Gemini response.', [
            'stage' => 'registration_extraction',
            'source' => $source,
            'reason' => $safeReason,
            'model' => trim((string) config('garage-assistant.gemini.model')),
        ]);

        $message = match ($safeReason) {
            'SPII' => 'Το Gemini δεν επέστρεψε στοιχεία λόγω προστασίας προσωπικών δεδομένων. Καταχώρισε τα στοιχεία χειροκίνητα.',
            'SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST', 'IMAGE_SAFETY' => 'Η αναγνώριση της εικόνας αποκλείστηκε από το Gemini. Δοκίμασε καθαρότερη φωτογραφία ή καταχώρισε τα στοιχεία χειροκίνητα.',
            'MAX_TOKENS' => 'Η αναγνώριση δεν ολοκληρώθηκε. Δοκίμασε ξανά με καθαρότερη φωτογραφία.',
            'MALFORMED_RESPONSE' => 'Το Gemini επέστρεψε μη έγκυρη δομημένη απάντηση. Δοκίμασε ξανά.',
            default => 'Το Gemini δεν επέστρεψε έγκυρη απάντηση. Δοκίμασε ξανά.',
        };

        throw ValidationException::withMessages([
            'registration_image' => $message,
        ]);
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 1);
    }

    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $digits === '' ? null : (int) $digits;
    }

    private function validVinOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $vin = trim($value);

        return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin) === 1 ? $vin : null;
    }

    private function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, trim($value));

                if ($date !== false && $date->format($format) === trim($value)) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Try the next explicitly supported document date format.
            }
        }

        return null;
    }
}
