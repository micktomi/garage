<?php

namespace App\Actions;

use App\Models\Appointment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAppointmentAction
{
    public function execute(array $data, User $actor, string $source = 'application'): Appointment
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
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($validated, $actor, $source): Appointment {
            $appointment = Appointment::create([...$validated, 'status' => 'scheduled']);

            Log::info('Appointment created', [
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'vehicle_id' => $appointment->vehicle_id,
                'actor_id' => $actor->id,
                'source' => $source,
            ]);

            return $appointment;
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->canAccessPanel(Filament::getPanel('admin'))) {
            throw new AuthorizationException;
        }
    }
}
