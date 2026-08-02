<?php

namespace App\Actions;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateWorkOrderAction
{
    public function execute(array $data, User $actor, string $source = 'application'): WorkOrder
    {
        $this->authorize($actor);

        $validated = Validator::make($data, [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(
                    fn ($query) => $query->where('customer_id', $data['customer_id'] ?? null)
                ),
            ],
            'problem_description' => ['required', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'work_performed' => ['nullable', 'string', 'max:5000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'next_service_date' => ['nullable', 'date'],
            'next_service_mileage' => ['nullable', 'integer', 'min:0'],
            'parts' => ['nullable', 'array'],
        ])->validate();

        return DB::transaction(function () use ($validated, $actor, $source): WorkOrder {
            $laborCost = (float) ($validated['labor_cost'] ?? 0);
            $workOrder = WorkOrder::create([
                'customer_id' => $validated['customer_id'],
                'vehicle_id' => $validated['vehicle_id'],
                'problem_description' => $validated['problem_description'],
                'diagnosis' => $validated['diagnosis'] ?? null,
                'work_performed' => $validated['work_performed'] ?? null,
                'labor_cost' => $laborCost,
                'parts_cost' => 0,
                'total_cost' => $laborCost,
                'current_mileage' => $validated['current_mileage'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'next_service_mileage' => $validated['next_service_mileage'] ?? null,
                'status' => WorkOrderStatus::New,
            ]);

            foreach ($validated['parts'] ?? [] as $row) {
                if (blank($row['source'] ?? null)) {
                    continue;
                }

                $quantity = (float) $row['quantity'];
                $unitPrice = (float) $row['unit_price'];
                $sourceType = $row['source'];

                WorkOrderPart::create([
                    'work_order_id' => $workOrder->id,
                    'source' => $sourceType,
                    'part_id' => $sourceType === 'from_stock' ? (int) $row['part_id'] : null,
                    'description' => $row['description'] ?? null,
                    'quantity' => $quantity,
                    'unit_cost' => $sourceType === 'customer_supplied' ? 0 : (float) ($row['unit_cost'] ?? 0),
                    'unit_price' => $unitPrice,
                    'line_total' => $quantity * $unitPrice,
                    'note' => $row['note'] ?? null,
                ]);
            }

            if (isset($validated['current_mileage'])) {
                $vehicle = Vehicle::find($validated['vehicle_id']);

                if ($vehicle && ($vehicle->mileage === null || $validated['current_mileage'] > $vehicle->mileage)) {
                    $vehicle->update(['mileage' => $validated['current_mileage']]);
                }
            }

            $workOrder = $workOrder->refresh();

            Log::info('Work order created', [
                'work_order_id' => $workOrder->id,
                'customer_id' => $workOrder->customer_id,
                'vehicle_id' => $workOrder->vehicle_id,
                'actor_id' => $actor->id,
                'source' => $source,
            ]);

            return $workOrder;
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->canAccessPanel(Filament::getPanel('admin'))) {
            throw new AuthorizationException;
        }
    }
}
