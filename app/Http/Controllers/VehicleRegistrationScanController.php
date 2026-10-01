<?php

namespace App\Http\Controllers;

use App\Actions\CreateCustomerVehicleFromRegistrationAction;
use App\Services\Assistant\VehicleRegistrationExtractor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

final class VehicleRegistrationScanController extends Controller
{
    public function show(Request $request)
    {
        if ($request->boolean('new')) {
            $request->session()->forget('workshop_registration_review');
        }
        $old = $request->session()->getOldInput();

        return Inertia::render('RegistrationScan/Index', [
            'extracted' => ($old['review_ready'] ?? false) ? $old : $request->session()->get('workshop_registration_review'),
            'modelsByMake' => WorkshopController::vehicleModelsByMake(),
        ]);
    }

    public function extract(Request $request, VehicleRegistrationExtractor $extractor)
    {
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

        $request->session()->put('workshop_registration_review', $extracted);

        return to_route('workshop.registration-scan.show');
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

        $request->session()->forget('workshop_registration_review');

        $message = $result['customer_reused']
            ? 'Το όχημα δημιουργήθηκε και συνδέθηκε με τον υπάρχοντα πελάτη του ίδιου ΑΦΜ.'
            : 'Ο πελάτης και το όχημα δημιουργήθηκαν μετά τον έλεγχό σου.';

        return redirect()
            ->route('workshop.vehicles.edit', $result['vehicle'])
            ->with('success', $message);
    }
}
