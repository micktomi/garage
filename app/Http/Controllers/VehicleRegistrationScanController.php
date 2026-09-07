<?php

namespace App\Http\Controllers;

use App\Actions\CreateCustomerVehicleFromRegistrationAction;
use App\Services\Assistant\VehicleRegistrationExtractor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class VehicleRegistrationScanController extends Controller
{
    public function show(): View
    {
        return view('workshop.registration-scan', ['extracted' => null]);
    }

    public function extract(Request $request, VehicleRegistrationExtractor $extractor): Response|RedirectResponse
    {
        $serverStartedAt = hrtime(true);
        $validated = $request->validate([
            'registration_image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'browser_preprocess_ms' => ['nullable', 'numeric', 'min:0', 'max:120000'],
        ], [
            'registration_image.required' => 'Τράβηξε ή επίλεξε φωτογραφία της άδειας.',
            'registration_image.image' => 'Το αρχείο πρέπει να είναι έγκυρη εικόνα.',
            'registration_image.mimes' => 'Υποστηρίζονται εικόνες JPEG, PNG και WebP.',
            'registration_image.max' => 'Η εικόνα δεν μπορεί να ξεπερνά τα 10 MB.',
        ]);

        try {
            $extracted = $extractor->extract($validated['registration_image']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Vehicle registration extraction request failed.', [
                'exception_class' => $exception::class,
            ]);

            return redirect()
                ->route('workshop.registration-scan.show')
                ->withErrors(['registration_image' => 'Το Gemini δεν είναι προσωρινά διαθέσιμο. Δοκίμασε ξανά.']);
        } catch (Throwable $exception) {
            Log::warning('Vehicle registration extraction failed safely.', [
                'exception_class' => $exception::class,
            ]);

            return redirect()
                ->route('workshop.registration-scan.show')
                ->withErrors(['registration_image' => 'Η αναγνώριση απέτυχε. Δεν αποθηκεύτηκε τίποτα.']);
        }

        $timings = [
            'browser_preprocess_ms' => round((float) ($validated['browser_preprocess_ms'] ?? 0), 1),
            ...$extractor->timings(),
            'server_total_ms' => round((hrtime(true) - $serverStartedAt) / 1_000_000, 1),
        ];

        $response = response()->view('workshop.registration-scan', compact('extracted', 'timings'));
        $response->headers->set('Server-Timing', sprintf(
            'gemini;dur=%.1f, decode;dur=%.1f, server;dur=%.1f',
            $timings['gemini_ms'],
            $timings['decode_validation_ms'],
            $timings['server_total_ms'],
        ));

        return $response;
    }

    public function store(Request $request, CreateCustomerVehicleFromRegistrationAction $action): RedirectResponse
    {
        try {
            $result = $action->execute($request->all(), $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('workshop.registration-scan.show')
                ->withErrors($exception->errors())
                ->withInput();
        }

        $message = $result['customer_reused']
            ? 'Το όχημα δημιουργήθηκε και συνδέθηκε με τον υπάρχοντα πελάτη του ίδιου ΑΦΜ.'
            : 'Ο πελάτης και το όχημα δημιουργήθηκαν μετά τον έλεγχό σου.';

        return redirect()
            ->route('workshop.vehicles.edit', $result['vehicle'])
            ->with('success', $message);
    }
}
