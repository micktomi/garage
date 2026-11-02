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
            'uploadMaxBytes' => $this->uploadMaxBytes(),
        ]);
    }

    public function extract(Request $request, VehicleRegistrationExtractor $extractor)
    {
        $validated = $request->validate([
            'registration_image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.intdiv($this->uploadMaxBytes(), 1024)],
            'browser_preprocess_ms' => ['nullable', 'numeric', 'min:0', 'max:120000'],
        ], [
            'registration_image.required' => 'Τράβηξε ή επίλεξε φωτογραφία της άδειας.',
            'registration_image.image' => 'Το αρχείο πρέπει να είναι έγκυρη εικόνα.',
            'registration_image.mimes' => 'Υποστηρίζονται εικόνες JPEG, PNG και WebP.',
            'registration_image.max' => 'Η εικόνα ξεπερνά το όριο μεταφόρτωσης του διακομιστή. Επιλέξτε μικρότερη εικόνα.',
            'registration_image.uploaded' => 'Η μεταφόρτωση της εικόνας απέτυχε. Δοκιμάστε μικρότερη εικόνα JPEG.',
        ]);

        try {
            $extracted = $extractor->extract($validated['registration_image']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (ConnectionException $exception) {
            Log::warning('Vehicle registration extraction request failed.', [
                'exception_class' => $exception::class,
            ]);

            return redirect()
                ->route('workshop.registration-scan.show')
                ->withErrors(['registration_image' => 'Η σύνδεση με την υπηρεσία αναγνώρισης δεν ολοκληρώθηκε. Δοκιμάστε ξανά σε λίγο.']);
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            $message = match ($status) {
                400 => 'Η υπηρεσία αναγνώρισης απέρριψε το αίτημα. Χρειάζεται έλεγχος των ρυθμίσεων από τον διαχειριστή.',
                401, 403 => 'Η πρόσβαση στην υπηρεσία αναγνώρισης δεν επιτρέπεται. Ο διαχειριστής πρέπει να ελέγξει το κλειδί και τα δικαιώματα Gemini.',
                404 => 'Το μοντέλο αναγνώρισης δεν είναι διαθέσιμο. Ο διαχειριστής πρέπει να ελέγξει τη ρύθμιση του μοντέλου Gemini.',
                429 => 'Η υπηρεσία αναγνώρισης έφτασε στο όριο χρήσης της. Δοκιμάστε αργότερα ή ζητήστε από τον διαχειριστή να ελέγξει το διαθέσιμο όριο.',
                default => 'Η υπηρεσία αναγνώρισης δεν είναι προσωρινά διαθέσιμη. Δοκιμάστε ξανά σε λίγο.',
            };

            return to_route('workshop.registration-scan.show')
                ->withErrors(['registration_image' => $message]);
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

    private function uploadMaxBytes(): int
    {
        $limit = 10 * 1024 * 1024;
        foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
            $bytes = ini_parse_quantity((string) ini_get($setting));
            if ($bytes > 0) {
                // POST must also fit the multipart headers, CSRF token and other fields.
                $limit = min($limit, $setting === 'post_max_size' ? max(1024, $bytes - 65536) : $bytes);
            }
        }

        return $limit;
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
