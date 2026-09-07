<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CreateCustomerVehicleFromRegistrationAction
{
    /**
     * @return array{customer: Customer, vehicle: Vehicle, customer_reused: bool}
     */
    public function execute(array $data, User $actor): array
    {
        $this->authorize($actor);

        $data['owner_full_name'] = trim((string) ($data['owner_full_name'] ?? ''));
        $data['plate_number'] = $this->normalizePlate((string) ($data['plate_number'] ?? ''));
        $data['vin'] = $this->normalizeVin($data['vin'] ?? null);
        $data['afm'] = $this->normalizeAfm($data['afm'] ?? null);

        $validated = Validator::make($data, [
            'owner_full_name' => ['required', 'string', 'max:255'],
            'afm' => ['nullable', 'digits:9'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/'],
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'fuel' => ['nullable', 'string', 'max:50'],
            'engine_cc' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'first_registered_at' => ['nullable', 'date_format:Y-m-d'],
            'confirm_near_vin' => ['nullable', 'boolean'],
        ], [
            'owner_full_name.required' => 'Το ονοματεπώνυμο ιδιοκτήτη είναι υποχρεωτικό.',
            'afm.digits' => 'Το ΑΦΜ πρέπει να έχει 9 ψηφία.',
            'plate_number.required' => 'Η πινακίδα είναι υποχρεωτική.',
            'plate_number.regex' => 'Η πινακίδα περιέχει μη έγκυρους χαρακτήρες.',
            'vin.size' => 'Το VIN πρέπει να έχει ακριβώς 17 χαρακτήρες.',
            'vin.regex' => 'Το VIN περιέχει μη έγκυρους ή αβέβαιους χαρακτήρες (I, O ή Q).',
            'engine_cc.integer' => 'Ο κυβισμός πρέπει να είναι ακέραιος αριθμός.',
            'first_registered_at.date_format' => 'Η πρώτη κυκλοφορία πρέπει να είναι έγκυρη ημερομηνία.',
        ])->validate();

        return DB::transaction(function () use ($validated): array {
            $this->rejectDuplicateVehicle(
                $validated['plate_number'],
                $validated['vin'] ?? null,
                (bool) ($validated['confirm_near_vin'] ?? false),
            );

            $customer = null;
            $customerReused = false;

            if (filled($validated['afm'] ?? null)) {
                $customer = Customer::where('afm', $validated['afm'])->first();
                $customerReused = $customer !== null;
            }

            $customer ??= Customer::create([
                'full_name' => $validated['owner_full_name'],
                'afm' => $validated['afm'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $firstRegisteredAt = $validated['first_registered_at'] ?? null;
            $vehicle = Vehicle::create([
                'customer_id' => $customer->id,
                'plate_number' => $validated['plate_number'],
                'vin' => $validated['vin'] ?? null,
                'make' => $validated['make'] ?? null,
                'model' => $validated['model'] ?? null,
                'year' => $firstRegisteredAt ? (int) substr($firstRegisteredAt, 0, 4) : null,
                'fuel' => $validated['fuel'] ?? null,
                'engine_cc' => $validated['engine_cc'] ?? null,
                'first_registered_at' => $firstRegisteredAt,
            ]);

            return [
                'customer' => $customer,
                'vehicle' => $vehicle,
                'customer_reused' => $customerReused,
            ];
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->canAccessPanel(Filament::getPanel('admin'))) {
            throw new AuthorizationException;
        }
    }

    private function rejectDuplicateVehicle(string $plate, ?string $vin, bool $nearVinConfirmed): void
    {
        $duplicatePlate = null;
        $duplicateVin = null;
        $nearVin = null;

        foreach (Vehicle::query()->select(['id', 'plate_number', 'vin'])->get() as $vehicle) {
            $existingPlate = $this->normalizePlate($vehicle->plate_number);
            $existingVin = $this->normalizeVin($vehicle->vin);

            if ($duplicatePlate === null && hash_equals($plate, $existingPlate)) {
                $duplicatePlate = $vehicle;
            }

            if ($vin === null || $existingVin === null) {
                continue;
            }

            if ($duplicateVin === null && hash_equals($vin, $existingVin)) {
                $duplicateVin = $vehicle;

                continue;
            }

            if ($nearVin === null && strlen($existingVin) === 17) {
                $distance = levenshtein($vin, $existingVin);

                if ($distance >= 1 && $distance <= 2) {
                    $nearVin = $vehicle;
                }
            }
        }

        $errors = [];

        if ($duplicatePlate !== null) {
            $errors['plate_number'] = 'Η κανονικοποιημένη πινακίδα υπάρχει ήδη καταχωρημένη. Δεν δημιουργήθηκε δεύτερο όχημα.';

            if ($vin !== null && $duplicatePlate->vin !== null) {
                $existingVin = $this->normalizeVin($duplicatePlate->vin);

                if ($existingVin !== null && strlen($existingVin) === 17) {
                    $distance = levenshtein($vin, $existingVin);

                    if ($distance >= 1 && $distance <= 2) {
                        $errors['near_vin'] = 'Η ίδια πινακίδα έχει VIN με μικρή διαφορά. Πρόκειται πιθανότατα για το ίδιο όχημα και απαιτείται χειροκίνητος έλεγχος της υπάρχουσας εγγραφής.';
                    }
                }
            }
        }

        if ($duplicateVin !== null) {
            $errors['vin'] = 'Το κανονικοποιημένο VIN υπάρχει ήδη καταχωρημένο. Δεν δημιουργήθηκε δεύτερο όχημα.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($nearVin !== null && ! $nearVinConfirmed) {
            throw ValidationException::withMessages([
                'near_vin' => 'Βρέθηκε υπάρχον VIN με απόσταση 1–2 χαρακτήρων. Έλεγξε ξανά το πεδίο (E) και επιβεβαίωσε ρητά πριν δημιουργήσεις διαφορετικό όχημα.',
            ]);
        }
    }

    private function normalizePlate(string $plate): string
    {
        $plate = mb_strtoupper(trim($plate), 'UTF-8');
        $plate = strtr($plate, [
            'Α' => 'A', 'Β' => 'B', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H',
            'Ι' => 'I', 'Κ' => 'K', 'Μ' => 'M', 'Ν' => 'N', 'Ο' => 'O',
            'Ρ' => 'P', 'Τ' => 'T', 'Υ' => 'Y', 'Χ' => 'X',
        ]);

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $plate) ?? '';
    }

    private function normalizeVin(mixed $vin): ?string
    {
        $vin = mb_strtoupper(preg_replace('/[\s-]+/u', '', trim((string) $vin)) ?? '', 'UTF-8');

        return $vin === '' ? null : $vin;
    }

    private function normalizeAfm(mixed $afm): ?string
    {
        $afm = preg_replace('/\D+/', '', (string) $afm) ?? '';

        return $afm === '' ? null : $afm;
    }
}
