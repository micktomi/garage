<?php

namespace App\Http\Middleware;

use App\Enums\WorkOrderStatus;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleWorkshopInertiaRequests extends Middleware
{
    protected $rootView = 'workshop-app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'role'),
                'canPrice' => $request->user()?->can('administer-pricing') ?? false,
            ],
            'flash' => ['success' => fn () => $request->session()->get('success')],
            'workOrderStatuses' => collect(WorkOrderStatus::cases())->map(fn ($status) => [
                'value' => $status->value, 'label' => $status->label(), 'tone' => $status->filamentColor(), 'open' => $status->isOpen(),
            ])->all(),
            'appointmentStatuses' => [
                ['value' => 'scheduled', 'label' => 'Προγραμματισμένο', 'tone' => 'info'],
                ['value' => 'in_progress', 'label' => 'Σε εξέλιξη', 'tone' => 'warning'],
                ['value' => 'completed', 'label' => 'Ολοκληρωμένο', 'tone' => 'success'],
                ['value' => 'cancelled', 'label' => 'Ακυρωμένο', 'tone' => 'danger'],
            ],
        ];
    }
}
