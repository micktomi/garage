<?php

namespace App\View\Components\Workshop;

use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class KteoRow extends Component
{
    public readonly string $customerName;

    public readonly string $plate;

    public readonly string $deadlineLabel;

    public readonly bool $expired;

    public readonly ?string $deadlineDateTime;

    public readonly ?string $phoneHref;

    public function __construct(public readonly Vehicle $vehicle)
    {
        $expiry = $vehicle->kteo_expires_at;
        $phone = preg_replace('/[^0-9+]/', '', (string) $vehicle->customer?->phone);

        $this->customerName = $vehicle->customer?->full_name ?: 'Χωρίς πελάτη';
        $this->plate = $vehicle->plate_number ?: '—';
        $this->expired = $expiry?->isBefore(today()) ?? false;
        $this->deadlineDateTime = $expiry?->toDateString();
        $this->phoneHref = filled($phone) ? $phone : null;

        if ($expiry === null) {
            $this->deadlineLabel = 'Χωρίς ημερομηνία ΚΤΕΟ';
        } elseif ($this->expired) {
            $this->deadlineLabel = 'Έληξε '.$expiry->format('d/m');
        } elseif ($expiry->isToday()) {
            $this->deadlineLabel = 'Λήγει σήμερα';
        } else {
            $this->deadlineLabel = 'Λήγει '.$expiry->format('d/m');
        }
    }

    public function render(): View
    {
        return view('components.workshop.kteo-row');
    }
}
