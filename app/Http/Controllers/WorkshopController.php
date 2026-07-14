<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        return view('workshop.dashboard', compact(
            'openWorkOrders',
            'todayAppointments',
            'kteoExpiring',
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

        $parts = Part::orderBy('name')->get(['id', 'name', 'quantity', 'sale_price']);

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
            ->get(['id', 'customer_id', 'plate_number', 'make', 'model'])
            ->groupBy('customer_id')
            ->map(function ($vehicles) {
                return $vehicles->map(function (Vehicle $vehicle) {
                    $label = $vehicle->plate_number ?? '—';
                    $makeModel = trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? ''));
                    if ($makeModel !== '') {
                        $label .= ' — ' . $makeModel;
                    }

                    return ['id' => $vehicle->id, 'label' => $label];
                })->values();
            });
    }

    public function workOrdersStore(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id'          => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(function ($query) use ($request) {
                    $query->where('customer_id', $request->input('customer_id'));
                }),
            ],
            'problem_description' => ['required', 'string', 'max:5000'],
            'labor_cost'          => ['nullable', 'numeric', 'min:0'],

            // single part card
            'part.source'       => ['nullable', 'string', 'in:from_stock,purchased_for_job,customer_supplied'],
            'part.part_id'      => ['nullable', 'integer', 'exists:parts,id'],
            'part.description'  => ['nullable', 'string', 'max:500'],
            'part.quantity'     => ['nullable', 'numeric', 'min:0.001'],
            'part.unit_cost'    => ['nullable', 'numeric', 'min:0'],
            'part.unit_price'   => ['nullable', 'numeric', 'min:0'],
        ], [
            'vehicle_id.exists' => 'Το επιλεγμένο όχημα δεν ανήκει στον επιλεγμένο πελάτη.',
        ]);

        $laborCost = (float) ($validated['labor_cost'] ?? 0);

        // ── Create the WorkOrder skeleton ──────────────────────────
        $workOrder = WorkOrder::create([
            'customer_id'         => $validated['customer_id'],
            'vehicle_id'          => $validated['vehicle_id'],
            'problem_description' => $validated['problem_description'],
            'labor_cost'          => $laborCost,
            'parts_cost'          => 0,
            'total_cost'          => $laborCost,
            'status'              => 'new',
        ]);

        // ── Process single part card ───────────────────────────────
        $partRow = $validated['part'] ?? [];
        $source  = $partRow['source'] ?? null;

        // A part is considered present only if source is set AND has the
        // required identifier for that source type.
        $hasPartId = ! empty($partRow['part_id']);
        $hasDesc   = ! empty(trim($partRow['description'] ?? ''));
        $partValid = $source &&
            (($source === 'from_stock' && $hasPartId) ||
             ($source !== 'from_stock' && $hasDesc));

        if ($partValid) {
            $qty       = max(0.001, (float) ($partRow['quantity']   ?? 1));
            $unitCost  = (float) ($partRow['unit_cost']  ?? 0);
            $unitPrice = (float) ($partRow['unit_price'] ?? 0);

            // customer_supplied: cost always 0
            if ($source === 'customer_supplied') {
                $unitCost = 0;
            }

            $lineTotal = $qty * $unitPrice;

            WorkOrderPart::create([
                'work_order_id' => $workOrder->id,
                'source'        => $source,
                'part_id'       => $source === 'from_stock' ? (int) $partRow['part_id'] : null,
                'description'   => $hasDesc ? $partRow['description'] : null,
                'quantity'      => $qty,
                'unit_cost'     => $unitCost,
                'unit_price'    => $unitPrice,
                'line_total'    => $lineTotal,
            ]);

            // WorkOrderPart::created event calls calculatePartsCost() —
            // refresh to get updated parts_cost / total_cost.
            $workOrder->refresh();
        }

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
            'new'         => 'Νέα',
            'in_progress' => 'Σε εξέλιξη',
            'completed'   => 'Ολοκληρωμένη',
            'cancelled'   => 'Ακυρωμένη',
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
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'phone'      => ['required', 'string', 'max:50'],
            'email'      => ['nullable', 'email', 'max:255'],
        ], [
            'first_name.required' => 'Το όνομα είναι υποχρεωτικό.',
            'first_name.max'      => 'Το όνομα είναι πολύ μεγάλο.',
            'last_name.required'  => 'Το επώνυμο είναι υποχρεωτικό.',
            'last_name.max'       => 'Το επώνυμο είναι πολύ μεγάλο.',
            'phone.required'      => 'Το τηλέφωνο είναι υποχρεωτικό.',
            'phone.max'           => 'Το τηλέφωνο είναι πολύ μεγάλο.',
            'email.email'        => 'Το email δεν είναι έγκυρο.',
            'email.max'           => 'Το email είναι πολύ μεγάλο.',
        ]);

        Customer::create([
            'full_name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'phone'     => $validated['phone'],
            'email'     => $validated['email'] ?? null,
        ]);

        return redirect()
            ->route('workshop.customers.create')
            ->with('success', 'Ο πελάτης δημιουργήθηκε.');
    }

    public function vehiclesCreate()
    {
        $customers = Customer::orderBy('full_name')->get(['id', 'full_name']);

        return view('workshop.vehicles.create', compact('customers'));
    }

    public function vehiclesStore(Request $request)
    {
        $validated = $request->validate([
            'customer_id'     => ['required', 'integer', 'exists:customers,id'],
            'license_plate'   => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'make'            => ['nullable', 'string', 'max:255'],
            'model'           => ['nullable', 'string', 'max:255'],
            'year'            => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'kteo_expires_at' => ['nullable', 'date'],
        ], [
            'customer_id.required'    => 'Επιλέξτε πελάτη.',
            'customer_id.exists'      => 'Ο επιλεγμένος πελάτης δεν βρέθηκε.',
            'license_plate.required'  => 'Η πινακίδα είναι υποχρεωτική.',
            'license_plate.max'       => 'Η πινακίδα είναι πολύ μεγάλη.',
            'license_plate.unique'    => 'Η πινακίδα υπάρχει ήδη καταχωρημένη.',
            'make.max'                => 'Η μάρκα είναι πολύ μεγάλη.',
            'model.max'               => 'Το μοντέλο είναι πολύ μεγάλο.',
            'year.integer'            => 'Το έτος πρέπει να είναι αριθμός.',
            'year.min'                => 'Το έτος δεν είναι έγκυρο.',
            'year.max'                => 'Το έτος δεν είναι έγκυρο.',
            'kteo_expires_at.date'    => 'Η ημερομηνία ΚΤΕΟ δεν είναι έγκυρη.',
        ]);

        Vehicle::create([
            'customer_id'     => $validated['customer_id'],
            'plate_number'    => $validated['license_plate'],
            'make'            => $validated['make'] ?? null,
            'model'           => $validated['model'] ?? null,
            'year'            => $validated['year'] ?? null,
            'kteo_expires_at' => $validated['kteo_expires_at'] ?? null,
        ]);

        return redirect()
            ->route('workshop.vehicles.create')
            ->with('success', 'Το όχημα προστέθηκε.');
    }

    public function kteoIndex()
    {
        $today = Carbon::today();
        $horizon = $today->copy()->addDays(30);

        $vehicles = Vehicle::with('customer')
            ->whereNotNull('kteo_expires_at')
            ->where('kteo_expires_at', '<=', $horizon)
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
        $validated = $request->validate([
            'customer_id'      => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id'       => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(function ($query) use ($request) {
                    $query->where('customer_id', $request->input('customer_id'));
                }),
            ],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'description'      => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_id.required'         => 'Επιλέξτε πελάτη.',
            'customer_id.exists'           => 'Ο επιλεγμένος πελάτης δεν βρέθηκε.',
            'vehicle_id.required'          => 'Επιλέξτε όχημα.',
            'vehicle_id.exists'            => 'Το επιλεγμένο όχημα δεν ανήκει στον επιλεγμένο πελάτη.',
            'appointment_date.required'    => 'Η ημερομηνία είναι υποχρεωτική.',
            'appointment_date.date'        => 'Η ημερομηνία δεν είναι έγκυρη.',
            'appointment_time.required'    => 'Η ώρα είναι υποχρεωτική.',
            'appointment_time.date_format' => 'Η ώρα δεν είναι έγκυρη.',
            'description.max'              => 'Η περιγραφή είναι πολύ μεγάλη.',
        ]);

        Appointment::create([
            'customer_id'      => $validated['customer_id'],
            'vehicle_id'       => $validated['vehicle_id'],
            'appointment_date' => Carbon::parse($validated['appointment_date'] . ' ' . $validated['appointment_time']),
            'description'      => $validated['description'] ?? null,
            'status'           => 'scheduled',
        ]);

        return redirect()
            ->route('workshop.appointments.index')
            ->with('success', 'Το ραντεβού καταχωρήθηκε.');
    }
}
