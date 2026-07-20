<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkshopController extends Controller
{
    public function dashboard()
    {
        $openWorkOrders = WorkOrder::whereIn('status', ['new', 'in_progress'])->count();
        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
        $kteoExpiring = Vehicle::whereNotNull('kteo_expires_at')
            ->where('kteo_expires_at', '<=', Carbon::today()->addDays(30))
            ->count();

        // Same filters as the counts above, just also returning the rows
        // so the dashboard can list them (not just show a number).
        $recentWorkOrders = WorkOrder::with(['customer', 'vehicle'])
            ->whereIn('status', ['new', 'in_progress'])
            ->latest()
            ->take(6)
            ->get();

        $todaysAppointments = Appointment::with(['customer', 'vehicle'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date')
            ->get();

        $expiringVehicles = Vehicle::with('customer')
            ->whereNotNull('kteo_expires_at')
            ->where('kteo_expires_at', '<=', Carbon::today()->addDays(30))
            ->orderBy('kteo_expires_at')
            ->take(6)
            ->get();

        return view('workshop.dashboard', compact(
            'openWorkOrders',
            'todayAppointments',
            'kteoExpiring',
            'recentWorkOrders',
            'todaysAppointments',
            'expiringVehicles',
        ));
    }

    public function workOrdersIndex()
    {
        $workOrders = WorkOrder::with(['customer', 'vehicle'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->get();

        return view('workshop.work-orders.index', compact('workOrders'));
    }

    public function workOrdersCreate()
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);

        $vehiclesByCustomer = $this->vehiclesGroupedByCustomer();

        $parts = Part::orderBy('name')->get(['id', 'name', 'quantity', 'purchase_price', 'sale_price']);

        return view('workshop.work-orders.create', compact('customers', 'vehiclesByCustomer', 'parts'));
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
     * Distinct vehicle makes — same source Filament's VehicleResource uses
     * for its searchable brand select, so both UIs suggest the same brands.
     */
    private function vehicleMakes()
    {
        return VehicleModel::query()
            ->whereNotNull('make')
            ->distinct()
            ->orderBy('make')
            ->pluck('make');
    }

    public function workOrdersStore(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(function ($query) use ($request) {
                    $query->where('customer_id', $request->input('customer_id'));
                }),
            ],
            'problem_description' => ['required', 'string', 'max:5000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date'],
            'next_service_mileage' => ['nullable', 'integer', 'min:0'],

            // repeater — every row is validated on its own terms, driven by
            // its own `source`. Rows with no source are treated as blank
            // placeholder rows and only get light type-checking.
            'parts' => ['nullable', 'array'],
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

        $laborCost = (float) ($validated['labor_cost'] ?? 0);

        $workOrder = DB::transaction(function () use ($validated, $laborCost) {
            // ── Create the WorkOrder skeleton ──────────────────────
            $workOrder = WorkOrder::create([
                'customer_id' => $validated['customer_id'],
                'vehicle_id' => $validated['vehicle_id'],
                'problem_description' => $validated['problem_description'],
                'labor_cost' => $laborCost,
                'parts_cost' => 0,
                'total_cost' => $laborCost,
                'current_mileage' => $validated['current_mileage'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'next_service_mileage' => $validated['next_service_mileage'] ?? null,
                'status' => 'new',
            ]);

            // ── Create every non-blank part row ─────────────────────
            foreach ($validated['parts'] ?? [] as $row) {
                $source = $row['source'] ?? null;

                if (! $source) {
                    continue;
                }

                $quantity = (float) $row['quantity'];
                $unitPrice = (float) $row['unit_price'];
                $unitCost = $source === 'customer_supplied' ? 0.0 : (float) ($row['unit_cost'] ?? 0);

                WorkOrderPart::create([
                    'work_order_id' => $workOrder->id,
                    'source' => $source,
                    'part_id' => $source === 'from_stock' ? (int) $row['part_id'] : null,
                    'description' => $row['description'] ?? null,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    // server-authoritative — never trust a client-computed total.
                    'line_total' => $quantity * $unitPrice,
                    'note' => $row['note'] ?? null,
                ]);
            }

            // Bump the vehicle's stored mileage from this work order's reading,
            // but never let an older/lower reading overwrite a higher one.
            if (! is_null($validated['current_mileage'] ?? null)) {
                $vehicle = Vehicle::find($validated['vehicle_id']);
                if ($vehicle && ($vehicle->mileage === null || $validated['current_mileage'] > $vehicle->mileage)) {
                    $vehicle->update(['mileage' => $validated['current_mileage']]);
                }
            }

            // WorkOrderPart::created events call calculatePartsCost() —
            // refresh to get the up-to-date parts_cost / total_cost.
            return $workOrder->refresh();
        });

        return redirect()
            ->route('workshop.work-orders.show', $workOrder)
            ->with('success', 'Η εντολή εργασίας δημιουργήθηκε.');
    }

    public function workOrdersShow(WorkOrder $workOrder)
    {
        $workOrder->load(['customer', 'vehicle', 'workOrderParts.part']);

        return view('workshop.work-orders.show', compact('workOrder'));
    }

    public function workOrdersUpdateStatus(Request $request, WorkOrder $workOrder)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:new,in_progress,completed,cancelled'],
        ]);

        $workOrder->update(['status' => $request->status]);

        $labels = [
            'new' => 'Νέα',
            'in_progress' => 'Σε εξέλιξη',
            'completed' => 'Ολοκληρωμένη',
            'cancelled' => 'Ακυρωμένη',
        ];

        $label = $labels[$request->status] ?? $request->status;

        return redirect()
            ->route('workshop.work-orders.show', $workOrder)
            ->with('success', "Κατάσταση → {$label}");
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
            ->get();

        return view('workshop.customers.index', compact('customers', 'q'));
    }

    public function customersCreate()
    {
        return view('workshop.customers.create');
    }

    public function customersStore(Request $request)
    {
        $partInput = $request->input('part', []);
        $partSource = $partInput['source'] ?? null;
        $hasSelectedPart = $partSource && (($partSource === 'from_stock' && ! empty($partInput['part_id']))
            || ($partSource !== 'from_stock' && ! empty(trim($partInput['description'] ?? ''))));

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
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

        Customer::create([
            'full_name' => trim($validated['first_name'].' '.$validated['last_name']),
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
        ]);

        return redirect()
            ->route('workshop.customers.create')
            ->with('success', 'Ο πελάτης δημιουργήθηκε.');
    }

    public function vehiclesCreate()
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);
        $makes = $this->vehicleMakes();

        return view('workshop.vehicles.create', compact('customers', 'makes'));
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
        $validated = $request->validate(
            $this->vehicleValidationRules(),
            $this->vehicleValidationMessages()
        );

        Vehicle::create([
            'customer_id' => $validated['customer_id'],
            'plate_number' => $validated['license_plate'],
            'make' => $validated['make'] ?? null,
            'model' => $validated['model'] ?? null,
            'year' => $validated['year'] ?? null,
            'vin' => $validated['vin'] ?? null,
            'mileage' => $validated['mileage'] ?? null,
            'kteo_expires_at' => $validated['kteo_expires_at'] ?? null,
        ]);

        return redirect()
            ->route('workshop.vehicles.create')
            ->with('success', 'Το όχημα προστέθηκε.');
    }

    public function vehiclesEdit(Vehicle $vehicle)
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);
        $makes = $this->vehicleMakes();

        return view('workshop.vehicles.edit', compact('vehicle', 'customers', 'makes'));
    }

    public function vehiclesUpdate(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate(
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
            ->where('kteo_expires_at', '<=', $horizon)
            ->orderByRaw('CASE WHEN kteo_expires_at < ? THEN 0 ELSE 1 END', [$today])
            ->orderBy('kteo_expires_at')
            ->get();

        return view('workshop.kteo.index', compact('vehicles', 'today'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $vehicles = collect();

        if ($q !== '') {
            // Normalize the plate query: uppercase, no spaces/dashes — so
            // "ab 1234" / "ab-1234" / "AB1234" all match the same plate.
            $normalized = strtoupper(str_replace([' ', '-'], '', $q));

            $vehicles = Vehicle::with(['customer', 'workOrders' => function ($query) {
                $query->whereIn('status', ['new', 'in_progress'])->latest();
            }])
                ->where(function ($query) use ($q, $normalized) {
                    $query->whereRaw("REPLACE(REPLACE(UPPER(plate_number), ' ', ''), '-', '') LIKE ?", ["%{$normalized}%"])
                        ->orWhereHas('customer', function ($customerQuery) use ($q) {
                            $customerQuery->where('full_name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                })
                ->orderBy('plate_number')
                ->get();
        }

        return view('workshop.search', compact('vehicles', 'q'));
    }

    public function appointmentsIndex()
    {
        $today = Carbon::today();

        $appointments = Appointment::with(['customer', 'vehicle'])
            ->where('appointment_date', '>=', $today)
            ->orderBy('appointment_date')
            ->get();

        return view('workshop.appointments.index', compact('appointments', 'today'));
    }

    public function appointmentsCreate()
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);

        $vehiclesByCustomer = $this->vehiclesGroupedByCustomer();

        return view('workshop.appointments.create', compact('customers', 'vehiclesByCustomer'));
    }

    public function appointmentsStore(Request $request)
    {
        $partInput = $request->input('part', []);
        $partSource = $partInput['source'] ?? null;
        $hasSelectedPart = $partSource && (($partSource === 'from_stock' && ! empty($partInput['part_id']))
            || ($partSource !== 'from_stock' && ! empty(trim($partInput['description'] ?? ''))));

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(function ($query) use ($request) {
                    $query->where('customer_id', $request->input('customer_id'));
                }),
            ],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_id.required' => 'Επιλέξτε πελάτη.',
            'customer_id.exists' => 'Ο επιλεγμένος πελάτης δεν βρέθηκε.',
            'vehicle_id.required' => 'Επιλέξτε όχημα.',
            'vehicle_id.exists' => 'Το επιλεγμένο όχημα δεν ανήκει στον επιλεγμένο πελάτη.',
            'appointment_date.required' => 'Η ημερομηνία είναι υποχρεωτική.',
            'appointment_date.date' => 'Η ημερομηνία δεν είναι έγκυρη.',
            'appointment_time.required' => 'Η ώρα είναι υποχρεωτική.',
            'appointment_time.date_format' => 'Η ώρα δεν είναι έγκυρη.',
            'description.max' => 'Η περιγραφή είναι πολύ μεγάλη.',
        ]);

        Appointment::create([
            'customer_id' => $validated['customer_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'appointment_date' => Carbon::parse($validated['appointment_date'].' '.$validated['appointment_time']),
            'description' => $validated['description'] ?? null,
            'status' => 'scheduled',
        ]);

        return redirect()
            ->route('workshop.appointments.index')
            ->with('success', 'Το ραντεβού καταχωρήθηκε.');
    }
}
