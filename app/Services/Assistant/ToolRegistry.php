<?php

namespace App\Services\Assistant;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\Assistant\DTOs\AssistantResponse;
use App\Services\Assistant\DTOs\ToolResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ToolRegistry
{
    public function __construct(private readonly ProposalStore $proposals) {}

    public function declarations(): array
    {
        return [
            $this->declaration('find_customer', 'Βρίσκει πελάτη μόνο με όνομα, τηλέφωνο ή email.', [
                'query' => $this->stringProperty('Όνομα, τηλέφωνο ή email πελάτη.'),
            ], ['query']),
            $this->declaration('find_vehicle', 'Βρίσκει όχημα κυρίως με πινακίδα.', [
                'query' => $this->stringProperty('Ολόκληρη ή μερική πινακίδα.'),
            ], ['query']),
            $this->declaration('get_vehicle_history', 'Επιστρέφει ελεγχόμενο ιστορικό εργασιών και ραντεβού οχήματος.', [
                'plate_number' => $this->stringProperty('Ολόκληρη ή μερική πινακίδα.'),
            ], ['plate_number']),
            $this->declaration('list_appointments', 'Λίστα ραντεβού για διάστημα έως 31 ημέρες.', [
                'date_from' => $this->stringProperty('Αρχική ημερομηνία YYYY-MM-DD.'),
                'date_to' => $this->stringProperty('Τελική ημερομηνία YYYY-MM-DD.'),
            ], ['date_from', 'date_to']),
            $this->declaration('list_open_work_orders', 'Λίστα εντολών με status new ή in_progress.', [], []),
            $this->declaration('prepare_appointment', 'Προετοιμάζει, αλλά δεν δημιουργεί, νέο ραντεβού.', [
                'plate_number' => $this->stringProperty('Πινακίδα οχήματος.'),
                'appointment_at' => $this->stringProperty('Ημερομηνία και ώρα ISO 8601.'),
                'description' => $this->stringProperty('Περιγραφή, αν υπάρχει.'),
            ], ['plate_number', 'appointment_at']),
            $this->declaration('prepare_work_order', 'Προετοιμάζει, αλλά δεν δημιουργεί, νέα εντολή εργασίας.', [
                'plate_number' => $this->stringProperty('Πινακίδα οχήματος.'),
                'problem_description' => $this->stringProperty('Περιγραφή προβλήματος ή ζητούμενης εργασίας.'),
                'current_mileage' => ['type' => 'INTEGER', 'description' => 'Τρέχοντα χιλιόμετρα, αν είναι γνωστά.'],
            ], ['plate_number', 'problem_description']),
        ];
    }

    public function execute(string $name, array $arguments, User $user): ToolResult
    {
        return match ($name) {
            'find_customer' => $this->findCustomer($arguments),
            'find_vehicle' => $this->findVehicle($arguments),
            'get_vehicle_history' => $this->getVehicleHistory($arguments),
            'list_appointments' => $this->listAppointments($arguments),
            'list_open_work_orders' => $this->listOpenWorkOrders(),
            'prepare_appointment' => $this->prepareAppointment($arguments, $user),
            'prepare_work_order' => $this->prepareWorkOrder($arguments, $user),
            default => throw ValidationException::withMessages(['tool' => 'Το ζητούμενο tool δεν επιτρέπεται.']),
        };
    }

    public function has(string $name): bool
    {
        return in_array($name, array_column($this->declarations(), 'name'), true);
    }

    private function findCustomer(array $arguments): ToolResult
    {
        $input = Validator::make($arguments, ['query' => ['required', 'string', 'max:255']])->validate();
        $query = trim($input['query']);
        $customers = Customer::query()
            ->with('vehicles:id,customer_id,plate_number,make,model')
            ->where(fn ($builder) => $builder
                ->where('full_name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%"))
            ->orderBy('full_name')
            ->limit(6)
            ->get();

        if ($customers->isEmpty()) {
            return ToolResult::terminal(AssistantResponse::direct('Δεν βρέθηκε πελάτης με αυτά τα στοιχεία.', ['status' => 'not_found']));
        }

        if ($customers->count() > 1) {
            return ToolResult::terminal(AssistantResponse::ambiguity(
                'Βρέθηκαν περισσότεροι από ένας πελάτες. Επίλεξε ποιον εννοείς.',
                $customers->map(fn (Customer $customer) => [
                    'label' => $customer->full_name.' · '.($customer->phone ?: 'χωρίς τηλέφωνο'),
                    'selection_prompt' => 'Εννοώ τον πελάτη '.$customer->full_name.($customer->phone ? ' με τηλέφωνο '.$customer->phone : '').'.',
                ])->all(),
            ));
        }

        $customer = $customers->first();

        return ToolResult::success('Βρέθηκε ένας πελάτης.', [
            'customer' => [
                'full_name' => $customer->full_name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'vehicles' => $customer->vehicles->map(fn (Vehicle $vehicle) => $this->vehicleData($vehicle))->all(),
            ],
        ]);
    }

    private function findVehicle(array $arguments): ToolResult
    {
        $input = Validator::make($arguments, ['query' => ['required', 'string', 'max:50']])->validate();
        $vehicles = $this->vehiclesMatching($input['query']);

        return $this->singleVehicleResult($vehicles, 'Βρέθηκε ένα όχημα.');
    }

    private function getVehicleHistory(array $arguments): ToolResult
    {
        $input = Validator::make($arguments, ['plate_number' => ['required', 'string', 'max:50']])->validate();
        $vehicles = $this->vehiclesMatching($input['plate_number']);
        $terminal = $this->vehicleResolutionTerminal($vehicles);

        if ($terminal) {
            return ToolResult::terminal($terminal);
        }

        $vehicle = $vehicles->first();
        $vehicle->load([
            'workOrders' => fn ($query) => $query->latest()->limit(25),
            'appointments' => fn ($query) => $query->latest('appointment_date')->limit(25),
        ]);

        return ToolResult::success('Βρέθηκε το ιστορικό του οχήματος.', [
            'vehicle' => $this->vehicleData($vehicle),
            'work_orders' => $vehicle->workOrders->map(fn (WorkOrder $order) => [
                'created_at' => $order->created_at?->toDateString(),
                'status' => $order->status,
                'problem_description' => $order->problem_description,
                'diagnosis' => $order->diagnosis,
                'work_performed' => $order->work_performed,
                'current_mileage' => $order->current_mileage,
                'total_cost' => (float) $order->total_cost,
            ])->all(),
            'appointments' => $vehicle->appointments->map(fn (Appointment $appointment) => [
                'appointment_date' => $appointment->appointment_date?->toIso8601String(),
                'status' => $appointment->status,
                'description' => $appointment->description,
            ])->all(),
        ]);
    }

    private function listAppointments(array $arguments): ToolResult
    {
        $input = Validator::make($arguments, [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ])->validate();
        $from = Carbon::createFromFormat('Y-m-d', $input['date_from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $input['date_to'])->endOfDay();

        if ($from->diffInDays($to) > 31) {
            throw ValidationException::withMessages(['date_to' => 'Το διάστημα δεν μπορεί να ξεπερνά τις 31 ημέρες.']);
        }

        $appointments = Appointment::with(['customer:id,full_name,phone', 'vehicle:id,plate_number,make,model'])
            ->whereBetween('appointment_date', [$from, $to])
            ->orderBy('appointment_date')
            ->limit(50)
            ->get();

        return ToolResult::success(
            $appointments->isEmpty() ? 'Δεν υπάρχουν ραντεβού στο επιλεγμένο διάστημα.' : 'Βρέθηκαν τα ραντεβού του διαστήματος.',
            ['appointments' => $appointments->map(fn (Appointment $appointment) => [
                'appointment_date' => $appointment->appointment_date?->toIso8601String(),
                'status' => $appointment->status,
                'description' => $appointment->description,
                'customer' => $appointment->customer?->full_name,
                'phone' => $appointment->customer?->phone,
                'plate_number' => $appointment->vehicle?->plate_number,
                'vehicle' => trim(($appointment->vehicle?->make ?? '').' '.($appointment->vehicle?->model ?? '')),
            ])->all()],
        );
    }

    private function listOpenWorkOrders(): ToolResult
    {
        $orders = WorkOrder::with(['customer:id,full_name', 'vehicle:id,plate_number,make,model'])
            ->whereIn('status', ['new', 'in_progress'])
            ->latest()
            ->limit(50)
            ->get();

        return ToolResult::success(
            $orders->isEmpty() ? 'Δεν υπάρχουν ανοιχτές εντολές εργασίας.' : 'Βρέθηκαν οι ανοιχτές εντολές εργασίας.',
            ['work_orders' => $orders->map(fn (WorkOrder $order) => [
                'created_at' => $order->created_at?->toIso8601String(),
                'status' => $order->status,
                'problem_description' => $order->problem_description,
                'customer' => $order->customer?->full_name,
                'plate_number' => $order->vehicle?->plate_number,
            ])->all()],
        );
    }

    private function prepareAppointment(array $arguments, User $user): ToolResult
    {
        $input = Validator::make($arguments, [
            'plate_number' => ['required', 'string', 'max:50'],
            'appointment_at' => ['required', 'date', 'after_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
        ])->validate();
        $vehicles = $this->vehiclesMatching($input['plate_number']);
        $terminal = $this->vehicleResolutionTerminal($vehicles);

        if ($terminal) {
            return ToolResult::terminal($terminal);
        }

        $vehicle = $vehicles->first();
        $appointmentAt = Carbon::parse($input['appointment_at']);
        $display = [
            'Πελάτης' => $vehicle->customer->full_name,
            'Όχημα' => $vehicle->plate_number.' · '.trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')),
            'Ημερομηνία / ώρα' => $appointmentAt->format('d/m/Y H:i'),
            'Περιγραφή' => $input['description'] ?? '—',
        ];
        $proposal = $this->proposals->create($user, 'create_appointment', 'Νέο ραντεβού', $display, [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => $appointmentAt->toDateTimeString(),
            'description' => $input['description'] ?? null,
        ]);

        return ToolResult::terminal(AssistantResponse::confirmation('Το ραντεβού είναι έτοιμο για έλεγχο. Δεν έχει γίνει καμία εγγραφή.', $proposal));
    }

    private function prepareWorkOrder(array $arguments, User $user): ToolResult
    {
        $input = Validator::make($arguments, [
            'plate_number' => ['required', 'string', 'max:50'],
            'problem_description' => ['required', 'string', 'max:5000'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
        ])->validate();
        $vehicles = $this->vehiclesMatching($input['plate_number']);
        $terminal = $this->vehicleResolutionTerminal($vehicles);

        if ($terminal) {
            return ToolResult::terminal($terminal);
        }

        $vehicle = $vehicles->first();
        $display = [
            'Πελάτης' => $vehicle->customer->full_name,
            'Όχημα' => $vehicle->plate_number.' · '.trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')),
            'Περιγραφή προβλήματος' => $input['problem_description'],
            'Τρέχοντα χιλιόμετρα' => isset($input['current_mileage']) ? number_format($input['current_mileage']).' km' : '—',
            'Αρχική κατάσταση' => 'Νέα',
        ];
        $proposal = $this->proposals->create($user, 'create_work_order', 'Νέα εντολή εργασίας', $display, [
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => $input['problem_description'],
            'current_mileage' => $input['current_mileage'] ?? null,
            'labor_cost' => 0,
        ]);

        return ToolResult::terminal(AssistantResponse::confirmation('Η εντολή εργασίας είναι έτοιμη για έλεγχο. Δεν έχει γίνει καμία εγγραφή.', $proposal));
    }

    private function vehiclesMatching(string $query): Collection
    {
        $normalized = mb_strtoupper(str_replace([' ', '-'], '', trim($query)), 'UTF-8');

        return Vehicle::with('customer:id,full_name,phone')
            ->whereRaw("REPLACE(REPLACE(plate_number, ' ', ''), '-', '') LIKE ?", ["%{$normalized}%"])
            ->orderBy('plate_number')
            ->limit(6)
            ->get();
    }

    private function singleVehicleResult(Collection $vehicles, string $message): ToolResult
    {
        $terminal = $this->vehicleResolutionTerminal($vehicles);

        if ($terminal) {
            return ToolResult::terminal($terminal);
        }

        return ToolResult::success($message, ['vehicle' => $this->vehicleData($vehicles->first())]);
    }

    private function vehicleResolutionTerminal(Collection $vehicles): ?AssistantResponse
    {
        if ($vehicles->isEmpty()) {
            return AssistantResponse::direct('Δεν βρέθηκε όχημα με αυτή την πινακίδα.', ['status' => 'not_found']);
        }

        if ($vehicles->count() > 1) {
            return AssistantResponse::ambiguity(
                'Βρέθηκαν περισσότερα από ένα οχήματα. Επίλεξε ποιο εννοείς.',
                $vehicles->map(fn (Vehicle $vehicle) => [
                    'label' => $vehicle->plate_number.' · '.$vehicle->customer->full_name.' · '.trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')),
                    'selection_prompt' => 'Εννοώ το όχημα με πινακίδα '.$vehicle->plate_number.'.',
                ])->all(),
            );
        }

        return null;
    }

    private function vehicleData(Vehicle $vehicle): array
    {
        return [
            'plate_number' => $vehicle->plate_number,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'mileage' => $vehicle->mileage,
            'vin' => $vehicle->vin,
            'customer' => $vehicle->customer?->full_name,
            'customer_phone' => $vehicle->customer?->phone,
        ];
    }

    private function declaration(string $name, string $description, array $properties, array $required): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'parameters' => array_filter([
                'type' => 'OBJECT',
                'properties' => (object) $properties,
                'required' => $required,
            ], fn ($value) => $value !== []),
        ];
    }

    private function stringProperty(string $description): array
    {
        return ['type' => 'STRING', 'description' => $description];
    }
}
