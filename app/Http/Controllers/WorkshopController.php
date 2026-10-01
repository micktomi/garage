<?php

namespace App\Http\Controllers;

use App\Actions\CreateAppointmentAction;
use App\Actions\CreateWorkOrderAction;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WorkshopController extends Controller
{
    private function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        return $request->validate($rules, $messages + [
            'required' => 'Το πεδίο :attribute είναι υποχρεωτικό.',
            'required_without' => 'Συμπληρώστε το πεδίο :attribute.',
            'string' => 'Το πεδίο :attribute πρέπει να είναι κείμενο.',
            'integer' => 'Το πεδίο :attribute πρέπει να είναι ακέραιος αριθμός.',
            'numeric' => 'Το πεδίο :attribute πρέπει να είναι αριθμός.',
            'email' => 'Συμπληρώστε έγκυρο email.',
            'exists' => 'Η επιλογή στο πεδίο :attribute δεν είναι διαθέσιμη.',
            'in' => 'Η επιλογή στο πεδίο :attribute δεν είναι έγκυρη.',
            'min.numeric' => 'Το πεδίο :attribute πρέπει να είναι τουλάχιστον :min.',
            'max.string' => 'Το πεδίο :attribute επιτρέπεται έως :max χαρακτήρες.',
            'date' => 'Συμπληρώστε έγκυρη ημερομηνία στο πεδίο :attribute.',
            'date_format' => 'Ελέγξτε τη μορφή στο πεδίο :attribute.',
            'after_or_equal' => 'Το πεδίο :attribute πρέπει να είναι από :date και μετά.',
        ], [
            'full_name' => 'ονοματεπώνυμο', 'customer_id' => 'πελάτης', 'vehicle_id' => 'όχημα',
            'problem_description' => 'πρόβλημα / αίτημα', 'phone' => 'τηλέφωνο', 'address' => 'διεύθυνση',
            'make' => 'μάρκα', 'model' => 'μοντέλο', 'plate_number' => 'πινακίδα',
            'appointment_date' => 'ημερομηνία ραντεβού', 'appointment_time' => 'ώρα ραντεβού',
            'parts.*.source' => 'προέλευση υλικού', 'parts.*.quantity' => 'ποσότητα υλικού',
            'parts.*.description' => 'περιγραφή υλικού', 'parts.*.part_id' => 'ανταλλακτικό',
        ]);
    }

    private function page(string $component, array $props = [])
    {
        if (isset($props['workOrder'])) {
            $props['canEdit'] = Gate::allows('amendCompleted', $props['workOrder']);
        }
        $props['defaults'] = request()->only('customer_id', 'vehicle_id') + ['idempotency_key' => (string) Str::uuid()];

        return Inertia::render($component, $props);
    }

    public function dashboard()
    {
        $today = Carbon::today();
        $kteoHorizon = $today->copy()->addDays(30);
        $openStatuses = WorkOrderStatus::openValues();

        $openWorkOrders = WorkOrder::whereIn('status', $openStatuses)->count();
        $todayAppointments = Appointment::whereDate('appointment_date', $today)->count();
        $awaitingParts = WorkOrder::where('status', WorkOrderStatus::AwaitingParts->value)->count();

        // The strip answers "what is on the floor now", the list below answers
        // "what is still owed to a customer" — deliberately different sets.
        $vehiclesInShop = WorkOrder::inShop()->distinct()->count('vehicle_id');
        $inShopWorkOrders = WorkOrder::with(['customer', 'vehicle'])
            ->inShop()
            ->latest()
            ->take(8)
            ->get();

        $recentWorkOrders = WorkOrder::with(['customer', 'vehicle'])
            ->whereIn('status', $openStatuses)
            ->latest()
            ->take(8)
            ->get();

        $kteoBase = fn () => Vehicle::with('customer')->whereNotNull('kteo_expires_at');

        // Already lapsed: oldest first, because that customer is overdue longest.
        $expiredKteo = $kteoBase()->whereDate('kteo_expires_at', '<', $today)->count();
        $expiredKteoVehicles = $kteoBase()
            ->whereDate('kteo_expires_at', '<', $today)
            ->orderBy('kteo_expires_at')
            ->take(5)
            ->get();

        $expiringKteo = $kteoBase()
            ->whereDate('kteo_expires_at', '>=', $today)
            ->whereDate('kteo_expires_at', '<=', $kteoHorizon)
            ->count();
        $expiringKteoVehicles = $kteoBase()
            ->whereDate('kteo_expires_at', '>=', $today)
            ->whereDate('kteo_expires_at', '<=', $kteoHorizon)
            ->orderBy('kteo_expires_at')
            ->take(5)
            ->get();

        $todayAppointmentRows = Appointment::with(['customer', 'vehicle'])->whereDate('appointment_date', $today)->orderBy('appointment_date')->take(8)->get();

        $todayLabel = Str::ucfirst($today->locale('el')->translatedFormat('l, j F Y'));

        return $this->page('Dashboard', compact(
            'openWorkOrders',
            'todayAppointments',
            'expiredKteo',
            'awaitingParts',
            'vehiclesInShop',
            'inShopWorkOrders',
            'recentWorkOrders',
            'expiredKteoVehicles',
            'expiringKteo',
            'expiringKteoVehicles',
            'todayLabel',
            'todayAppointmentRows',
        ));
    }

    public function workOrdersIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'open');
        $workOrders = WorkOrder::with(['customer', 'vehicle'])
            ->when($status === 'open', fn ($query) => $query->whereIn('status', WorkOrderStatus::openValues()))
            ->when($status === 'archive', fn ($query) => $query->whereNotIn('status', WorkOrderStatus::openValues()))
            ->when(in_array($status, array_column(WorkOrderStatus::cases(), 'value')), fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub
                ->where('problem_description', 'like', "%{$q}%")
                ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))
                ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$q}%"))))
            ->latest()->paginate(20)->withQueryString();

        return $this->page('WorkOrders/Index', compact('workOrders', 'q', 'status'));
    }

    public function workOrdersCreate()
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);

        $vehiclesByCustomer = $this->vehiclesGroupedByCustomer();

        $parts = Part::orderBy('name')->get(['id', 'name', 'quantity', 'purchase_price', 'sale_price']);

        return $this->page('WorkOrders/Form', compact('customers', 'vehiclesByCustomer', 'parts'));
    }

    /**
     * Vehicles grouped by customer_id, with a ready-to-display label —
     * shared by the work order and appointment create forms so the
     * "vehicle depends on customer" dropdown behaves identically.
     */
    private function vehiclesGroupedByCustomer()
    {
        return Vehicle::orderBy('plate_number')
            ->get(['id', 'customer_id', 'plate_number', 'make', 'model', 'mileage'])
            ->groupBy('customer_id')
            ->map(function ($vehicles) {
                return $vehicles->map(function (Vehicle $vehicle) {
                    $label = $vehicle->plate_number ?? '—';
                    $makeModel = trim(($vehicle->make ?? '').' '.($vehicle->model ?? ''));
                    if ($makeModel !== '') {
                        $label .= ' — '.$makeModel;
                    }

                    return ['id' => $vehicle->id, 'label' => $label, 'mileage' => $vehicle->mileage];
                })->values();
            });
    }

    /**
     * Curated vehicle models grouped by make for the workshop vehicle forms.
     * The same vehicle_models source powers Filament's dependent model datalist.
     *
     * @return array<string, array<int, string>>
     */
    public static function vehicleModelsByMake(): array
    {
        return VehicleModel::query()
            ->select(['make', 'model'])
            ->whereNotNull('make')
            ->where('make', '!=', '')
            ->whereNotNull('model')
            ->where('model', '!=', '')
            ->distinct()
            ->orderBy('make')
            ->orderBy('model')
            ->get()
            ->map(fn (VehicleModel $vehicleModel): array => [
                'make' => trim($vehicleModel->make),
                'model' => trim($vehicleModel->model),
            ])
            ->filter(fn (array $vehicleModel): bool => $vehicleModel['make'] !== '' && $vehicleModel['model'] !== '')
            ->groupBy('make')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($models): array => $models
                ->pluck('model')
                ->unique(fn (string $model): string => Str::lower($model))
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all())
            ->all();
    }

    private function validateWorkOrder(Request $request, ?WorkOrder $workOrder = null): array
    {
        return $this->validateRequest($request, [
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(function ($query) use ($request) {
                    $query->where('customer_id', $request->input('customer_id'));
                }),
            ],
            'problem_description' => ['required', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'work_performed' => ['nullable', 'string', 'max:5000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date'],
            'next_service_mileage' => ['nullable', 'integer', 'min:0'],

            // repeater — every row is validated on its own terms, driven by
            // its own `source`. Rows with no source are treated as blank
            // placeholder rows and only get light type-checking.
            'parts' => ['nullable', 'array'],
            'parts.*.id' => ['nullable', 'integer', Rule::exists('work_order_parts', 'id')->where('work_order_id', $workOrder?->id ?? 0)],
            'parts.*' => Rule::forEach(function ($value) {
                $source = is_array($value) ? ($value['source'] ?? null) : null;

                if (! $source) {
                    return [
                        'source' => ['nullable', 'string', 'in:from_stock,customer_supplied,purchased_for_job'],
                        'part_id' => ['nullable', 'integer', 'exists:parts,id'],
                        'description' => ['nullable', 'string', 'max:500'],
                        'quantity' => ['nullable', 'numeric', 'min:0.5'],
                        'unit_cost' => ['nullable', 'numeric', 'min:0'],
                        'unit_price' => ['nullable', 'numeric', 'min:0.01'],
                        'note' => ['nullable', 'string', 'max:255'],
                    ];
                }

                return [
                    'source' => ['required', 'string', 'in:from_stock,customer_supplied,purchased_for_job'],
                    'part_id' => $source === 'from_stock'
                        ? ['required', 'integer', 'exists:parts,id']
                        : ['prohibited'],
                    'description' => $source === 'from_stock'
                        ? ['nullable', 'string', 'max:500']
                        : ['required', 'string', 'max:500'],
                    'quantity' => ['required', 'numeric', 'min:0.5'],
                    'unit_cost' => ['nullable', 'numeric', 'min:0'],
                    'unit_price' => ['required', 'numeric', 'min:0.01'],
                    'note' => ['nullable', 'string', 'max:255'],
                ];
            }),
        ], [
            'vehicle_id.exists' => 'Το επιλεγμένο όχημα δεν ανήκει στον επιλεγμένο πελάτη.',
        ]);
    }

    public function workOrdersStore(Request $request, CreateWorkOrderAction $createWorkOrder)
    {
        $validated = $this->validateWorkOrder($request);

        // Purchase cost is margin data, not counter data. Rather than
        // rejecting the submission, fall back to the value the catalogue
        // already holds — the row is still recorded, just not repriced by
        // someone who is not allowed to reprice it.
        if (Gate::denies('administer-pricing')) {
            $validated['parts'] = $this->costsFromCatalogue($validated['parts'] ?? []);
        }

        // Replay guard. The fast path is a plain lookup; the unique index on
        // idempotency_key is what actually decides a genuine race, so a losing
        // insert is resolved by re-reading the winner rather than by locking.
        $token = $validated['idempotency_key'] ?? null;

        if ($token !== null && $replayed = WorkOrder::where('idempotency_key', $token)->first()) {
            return redirect()
                ->route('workshop.work-orders.show', $replayed)
                ->with('success', 'Η εντολή εργασίας είχε ήδη καταχωρηθεί.');
        }

        try {
            $workOrder = $createWorkOrder->execute($validated, $request->user(), 'workshop');
        } catch (UniqueConstraintViolationException $exception) {
            $winner = $token === null ? null : WorkOrder::where('idempotency_key', $token)->first();

            if ($winner === null) {
                throw $exception;
            }

            return redirect()
                ->route('workshop.work-orders.show', $winner)
                ->with('success', 'Η εντολή εργασίας είχε ήδη καταχωρηθεί.');
        }

        return redirect()
            ->route('workshop.work-orders.show', $workOrder)
            ->with('success', 'Η εντολή εργασίας δημιουργήθηκε.');
    }

    /**
     * Replaces every user-supplied unit_cost with the stock part's own
     * purchase price (and 0 for anything not taken from stock, matching what
     * CreateWorkOrderAction already does for customer-supplied lines).
     *
     * @param  array<int, array<string, mixed>>  $parts
     * @return array<int, array<string, mixed>>
     */
    private function costsFromCatalogue(array $parts, ?WorkOrder $workOrder = null): array
    {
        $catalogue = Part::query()
            ->whereIn('id', collect($parts)->pluck('part_id')->filter()->all())
            ->pluck('purchase_price', 'id');

        return collect($parts)
            ->map(function (array $row) use ($catalogue, $workOrder): array {
                $existing = isset($row['id']) ? $workOrder?->workOrderParts()->find($row['id']) : null;
                if ($existing && $existing->source === $row['source'] && ($row['source'] !== 'from_stock' || $existing->part_id == ($row['part_id'] ?? null))) {
                    $row['unit_cost'] = (float) $existing->unit_cost;

                    return $row;
                }
                $row['unit_cost'] = ($row['source'] ?? null) === 'from_stock'
                    ? (float) ($catalogue[$row['part_id'] ?? null] ?? 0)
                    : 0;

                return $row;
            })
            ->all();
    }

    public function workOrdersShow(WorkOrder $workOrder)
    {
        $workOrder->load(['customer', 'vehicle', 'workOrderParts.part']);

        return $this->page('WorkOrders/Show', compact('workOrder'));
    }

    public function workOrdersUpdateStatus(Request $request, WorkOrder $workOrder)
    {
        $validated = $this->validateRequest($request, [
            // Required, not optional: an omitted token is a page rendered
            // before this guard existed, which is the same stale
            // representation the guard is here to refuse.
            'lock_version' => ['required', 'integer'],
            'status' => ['required', Rule::enum(WorkOrderStatus::class)],
        ], [
            'lock_version.required' => 'Ανανεώστε τη σελίδα και δοκιμάστε ξανά.',
        ]);

        // Only bites once the order is already Completed — closing an open
        // one is ordinary counter work. See WorkOrderPolicy::amendCompleted().
        Gate::authorize('amendCompleted', $workOrder);

        $status = WorkOrderStatus::from($validated['status']);

        // Atomic compare-and-swap — see WorkOrder::updateWithExpectedVersion().
        // Throws the same ValidationException(['lock_version' => ...]) the
        // old pre-check did, so a stale submission still redirects back with
        // the identical session error.
        $workOrder->updateWithExpectedVersion($validated['lock_version'], [
            'status' => $status,
        ]);

        return redirect()
            ->route('workshop.work-orders.show', $workOrder)
            ->with('success', "Κατάσταση → {$status->label()}");
    }

    public function customersIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->with('vehicles')
            ->withCount('vehicles')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhereHas('vehicles', function ($vehicleQuery) use ($q) {
                            $vehicleQuery->where('plate_number', 'like', "%{$q}%");
                        });
                });
            })
            ->orderBy('full_name')
            ->paginate(20)->withQueryString();

        return $this->page('Customers/Index', compact('customers', 'q'));
    }

    public function customersCreate()
    {
        return $this->page('Customers/Form');
    }

    public function customersStore(Request $request)
    {
        $validated = $this->validateRequest($request, [
            'full_name' => ['required_without:first_name', 'nullable', 'string', 'max:255'],
            'first_name' => ['required_without:full_name', 'nullable', 'string', 'max:255'],
            'last_name' => ['required_without:full_name', 'nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'first_name.required' => 'Το όνομα είναι υποχρεωτικό.',
            'first_name.max' => 'Το όνομα είναι πολύ μεγάλο.',
            'last_name.required' => 'Το επώνυμο είναι υποχρεωτικό.',
            'last_name.max' => 'Το επώνυμο είναι πολύ μεγάλο.',
            'phone.required' => 'Το τηλέφωνο είναι υποχρεωτικό.',
            'phone.max' => 'Το τηλέφωνο είναι πολύ μεγάλο.',
            'email.email' => 'Το email δεν είναι έγκυρο.',
            'email.max' => 'Το email είναι πολύ μεγάλο.',
        ]);

        $customer = Customer::create([
            'full_name' => $validated['full_name'] ?? trim($validated['first_name'].' '.$validated['last_name']),
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
        ]);

        return redirect()
            ->route('workshop.customers.show', $customer)
            ->with('success', 'Ο πελάτης δημιουργήθηκε.');
    }

    public function vehiclesCreate()
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);
        $modelsByMake = $this->vehicleModelsByMake();
        $makes = array_keys($modelsByMake);

        return $this->page('Vehicles/Form', compact('customers', 'makes', 'modelsByMake'));
    }

    private function vehicleValidationRules(?Vehicle $vehicle = null): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'license_plate' => [
                'required', 'string', 'max:20',
                Rule::unique('vehicles', 'plate_number')->ignore($vehicle?->id),
            ],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'vin' => ['nullable', 'string', 'max:50'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'kteo_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function vehicleValidationMessages(): array
    {
        return [
            'customer_id.required' => 'Επιλέξτε πελάτη.',
            'customer_id.exists' => 'Ο επιλεγμένος πελάτης δεν βρέθηκε.',
            'license_plate.required' => 'Η πινακίδα είναι υποχρεωτική.',
            'license_plate.max' => 'Η πινακίδα είναι πολύ μεγάλη.',
            'license_plate.unique' => 'Η πινακίδα υπάρχει ήδη καταχωρημένη.',
            'make.max' => 'Η μάρκα είναι πολύ μεγάλη.',
            'model.max' => 'Το μοντέλο είναι πολύ μεγάλο.',
            'year.integer' => 'Το έτος πρέπει να είναι αριθμός.',
            'year.min' => 'Το έτος δεν είναι έγκυρο.',
            'year.max' => 'Το έτος δεν είναι έγκυρο.',
            'vin.max' => 'Το VIN / αρ. πλαισίου είναι πολύ μεγάλο.',
            'mileage.integer' => 'Τα χιλιόμετρα πρέπει να είναι αριθμός.',
            'mileage.min' => 'Τα χιλιόμετρα δεν είναι έγκυρα.',
            'kteo_expires_at.date' => 'Η ημερομηνία ΚΤΕΟ δεν είναι έγκυρη.',
        ];
    }

    public function vehiclesStore(Request $request)
    {
        $validated = $this->validateRequest($request,
            $this->vehicleValidationRules(),
            $this->vehicleValidationMessages()
        );

        $vehicle = Vehicle::create([
            'customer_id' => $validated['customer_id'],
            'plate_number' => $validated['license_plate'],
            'make' => $validated['make'] ?? null,
            'model' => $validated['model'] ?? null,
            'year' => $validated['year'] ?? null,
            'vin' => $validated['vin'] ?? null,
            'mileage' => $validated['mileage'] ?? null,
            'kteo_expires_at' => $validated['kteo_expires_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('workshop.vehicles.edit', $vehicle)
            ->with('success', 'Το όχημα προστέθηκε.');
    }

    public function vehiclesEdit(Vehicle $vehicle)
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);
        $modelsByMake = $this->vehicleModelsByMake();
        $makes = array_keys($modelsByMake);

        // Read-only history for this vehicle — presentation data only,
        // no new business logic.
        $vehicleWorkOrders = WorkOrder::where('vehicle_id', $vehicle->id)->latest()->paginate(20, ['*'], 'work_orders_page')->withQueryString();
        $vehicleAppointments = Appointment::where('vehicle_id', $vehicle->id)->orderByDesc('appointment_date')->paginate(20, ['*'], 'appointments_page')->withQueryString();

        return $this->page('Vehicles/Form', compact(
            'vehicle',
            'customers',
            'makes',
            'modelsByMake',
            'vehicleWorkOrders',
            'vehicleAppointments',
        ));
    }

    public function vehiclesUpdate(Request $request, Vehicle $vehicle)
    {
        $validated = $this->validateRequest($request,
            $this->vehicleValidationRules($vehicle),
            $this->vehicleValidationMessages()
        );

        // Manual edits may correct a wrong mileage in either direction —
        // unlike the work-order flow, there's no "never decrease" guard here.
        $vehicle->update([
            'customer_id' => $validated['customer_id'],
            'plate_number' => $validated['license_plate'],
            'make' => $validated['make'] ?? null,
            'model' => $validated['model'] ?? null,
            'year' => $validated['year'] ?? null,
            'vin' => $validated['vin'] ?? null,
            'mileage' => $validated['mileage'] ?? null,
            'kteo_expires_at' => $validated['kteo_expires_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('workshop.vehicles.edit', $vehicle)
            ->with('success', 'Το όχημα ενημερώθηκε.');
    }

    public function kteoIndex()
    {
        $today = Carbon::today();
        $horizon = $today->copy()->addDays(30);

        $vehicles = Vehicle::with('customer')
            ->whereNotNull('kteo_expires_at')
            ->whereDate('kteo_expires_at', '<=', $horizon)
            ->orderByRaw('CASE WHEN kteo_expires_at < ? THEN 0 ELSE 1 END', [$today])
            ->orderBy('kteo_expires_at')
            ->paginate(20)->withQueryString();

        return $this->page('Kteo/Index', compact('vehicles', 'today'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $customers = Customer::where(fn ($c) => $c->where('full_name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))->orderBy('full_name')->paginate(20, ['*'], 'customers_page')->withQueryString();

        if ($q === '') {
            $vehicles = Vehicle::with(['customer', 'workOrders' => function ($query) {
                $query->whereIn('status', WorkOrderStatus::openValues())->latest();
            }])
                ->orderBy('plate_number')
                ->paginate(12)
                ->withQueryString();

            return $this->page('Search', compact('vehicles', 'q', 'customers'));
        }

        // Normalize the plate query: uppercase, no spaces/dashes — so
        // "ab 1234" / "ab-1234" / "AB1234" all match the same plate.
        $normalized = strtoupper(str_replace([' ', '-'], '', $q));

        $vehicles = Vehicle::with(['customer', 'workOrders' => function ($query) {
            $query->whereIn('status', WorkOrderStatus::openValues())->latest();
        }])
            ->where(function ($query) use ($q, $normalized) {
                $query->whereRaw("REPLACE(REPLACE(UPPER(plate_number), ' ', ''), '-', '') LIKE ?", ["%{$normalized}%"])
                    ->orWhereHas('customer', function ($customerQuery) use ($q) {
                        $customerQuery->where('full_name', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    });
            })
            ->orderBy('plate_number')
            ->paginate(20)->withQueryString();

        return $this->page('Search', compact('vehicles', 'q', 'customers'));
    }

    public function appointmentsIndex(Request $request)
    {
        $today = Carbon::today();

        $appointments = Appointment::with(['customer', 'vehicle'])
            ->when(! $request->boolean('history'), fn ($query) => $query->whereDate('appointment_date', '>=', $today))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderBy('appointment_date')
            ->paginate(20)->withQueryString();

        return $this->page('Appointments/Index', ['appointments' => $appointments, 'today' => $today, 'filters' => $request->only('history', 'status')]);
    }

    public function appointmentsCreate()
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);

        $vehiclesByCustomer = $this->vehiclesGroupedByCustomer();

        return $this->page('Appointments/Form', compact('customers', 'vehiclesByCustomer'));
    }

    public function appointmentsStore(Request $request, CreateAppointmentAction $createAppointment)
    {
        $validated = $this->validateRequest($request, [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(function ($query) use ($request) {
                    $query->where('customer_id', $request->input('customer_id'));
                }),
            ],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_id.required' => 'Επιλέξτε πελάτη.',
            'customer_id.exists' => 'Ο επιλεγμένος πελάτης δεν βρέθηκε.',
            'vehicle_id.required' => 'Επιλέξτε όχημα.',
            'vehicle_id.exists' => 'Το επιλεγμένο όχημα δεν ανήκει στον επιλεγμένο πελάτη.',
            'appointment_date.required' => 'Η ημερομηνία είναι υποχρεωτική.',
            'appointment_date.date' => 'Η ημερομηνία δεν είναι έγκυρη.',
            'appointment_date.after_or_equal' => 'Η ημερομηνία δεν μπορεί να είναι στο παρελθόν.',
            'appointment_time.required' => 'Η ώρα είναι υποχρεωτική.',
            'appointment_time.date_format' => 'Η ώρα δεν είναι έγκυρη.',
            'description.max' => 'Η περιγραφή είναι πολύ μεγάλη.',
        ]);

        $createAppointment->execute([
            'customer_id' => $validated['customer_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'appointment_date' => Carbon::parse($validated['appointment_date'].' '.$validated['appointment_time']),
            'description' => $validated['description'] ?? null,
        ], $request->user(), 'workshop');

        return redirect()
            ->route('workshop.appointments.index')
            ->with('success', 'Το ραντεβού καταχωρήθηκε.');
    }

    public function workOrdersEdit(WorkOrder $workOrder)
    {
        Gate::authorize('amendCompleted', $workOrder);
        $workOrder->load('workOrderParts.part');

        return Inertia::render('WorkOrders/Form', [
            'workOrder' => $workOrder,
            'customers' => Customer::orderBy('full_name')->get(['id', 'full_name']),
            'vehiclesByCustomer' => $this->vehiclesGroupedByCustomer(),
            'parts' => Part::orderBy('name')->get(),
        ]);
    }

    public function workOrdersUpdate(Request $request, WorkOrder $workOrder)
    {
        Gate::authorize('amendCompleted', $workOrder);
        $version = $this->validateRequest($request, ['lock_version' => ['required', 'integer']])['lock_version'];
        $validated = $this->validateWorkOrder($request, $workOrder);
        if (Gate::denies('administer-pricing')) {
            $validated['parts'] = $this->costsFromCatalogue($validated['parts'] ?? [], $workOrder);
        }
        DB::transaction(function () use ($workOrder, $version, $validated) {
            $lines = $validated['parts'] ?? [];
            $attributes = collect($validated)->except(['parts', 'idempotency_key'])->all();
            $workOrder->updateWithExpectedVersion($version, $attributes);
            // Replace lines through existing model hooks: no controller stock arithmetic.
            foreach ($workOrder->workOrderParts()->get() as $line) {
                $line->delete();
            }
            foreach ($lines as $line) {
                if (blank($line['source'] ?? null)) {
                    continue;
                }
                WorkOrderPart::create([
                    ...$line,
                    'work_order_id' => $workOrder->id,
                    'part_id' => $line['source'] === 'from_stock' ? $line['part_id'] : null,
                    'unit_cost' => $line['source'] === 'customer_supplied' ? 0 : ($line['unit_cost'] ?? 0),
                    'line_total' => $line['quantity'] * $line['unit_price'],
                ]);
            }
            $workOrder->calculatePartsCost();
            if (isset($validated['current_mileage'])) {
                Vehicle::whereKey($validated['vehicle_id'])
                    ->where(fn ($q) => $q->whereNull('mileage')->orWhere('mileage', '<', $validated['current_mileage']))
                    ->update(['mileage' => $validated['current_mileage']]);
            }
        });

        return to_route('workshop.work-orders.show', $workOrder)->with('success', 'Η εντολή ενημερώθηκε.');
    }

    public function customersShow(Customer $customer)
    {
        Gate::authorize('view', $customer);

        return Inertia::render('Customers/Show', [
            'customer' => $customer->load('vehicles'),
            'workOrders' => $customer->workOrders()->with('vehicle')->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function customersEdit(Customer $customer)
    {
        Gate::authorize('update', $customer);

        return Inertia::render('Customers/Form', compact('customer'));
    }

    public function customersUpdate(Request $request, Customer $customer)
    {
        Gate::authorize('update', $customer);
        $customer->update($this->validateRequest($request, [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]));

        return to_route('workshop.customers.show', $customer)->with('success', 'Ο πελάτης ενημερώθηκε.');
    }

    public function vehiclesIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $vehicles = Vehicle::with('customer')->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub
            ->where('plate_number', 'like', "%{$q}%")->orWhere('make', 'like', "%{$q}%")->orWhere('model', 'like', "%{$q}%")
            ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))))
            ->orderBy('plate_number')->paginate(20)->withQueryString();

        return Inertia::render('Vehicles/Index', compact('vehicles', 'q'));
    }

    public function appointmentsEdit(Appointment $appointment)
    {
        Gate::authorize('update', $appointment);

        return Inertia::render('Appointments/Form', [
            'appointment' => $appointment,
            'customers' => Customer::orderBy('full_name')->get(['id', 'full_name']),
            'vehiclesByCustomer' => $this->vehiclesGroupedByCustomer(),
        ]);
    }

    public function appointmentsUpdate(Request $request, Appointment $appointment)
    {
        Gate::authorize('update', $appointment);
        $validated = $this->validateRequest($request, [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->where('customer_id', $request->input('customer_id'))],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
        ]);
        $validated['appointment_date'] = Carbon::parse($validated['appointment_date'].' '.$validated['appointment_time']);
        unset($validated['appointment_time']);
        $appointment->update($validated);

        return to_route('workshop.appointments.index')->with('success', 'Το ραντεβού ενημερώθηκε.');
    }

    public function partsIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $parts = Part::select(['id', 'code', 'name', 'quantity', 'sale_price'])->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub->where('code', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Parts/Index', compact('parts', 'q'));
    }
}
