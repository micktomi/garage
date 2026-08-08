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

    /** Full customer name, only when the row is too narrow to show it whole. */
    public readonly ?string $customerTooltip;

    public function __construct(public readonly Vehicle $vehicle)
    {
        $expiry = $vehicle->kteo_expires_at;
        $phone = preg_replace('/[^0-9+]/', '', (string) $vehicle->customer?->phone);
        $today = today();

        $this->customerName = $vehicle->customer?->full_name ?: 'Χωρίς πελάτη';
        $this->customerTooltip = mb_strlen($this->customerName) > 24 ? $this->customerName : null;
        $this->plate = $vehicle->plate_number ?: '—';
        $this->expired = $expiry?->isBefore($today) ?? false;
        $this->deadlineDateTime = $expiry?->toDateString();
        $this->phoneHref = filled($phone) ? $phone : null;

        // The day count carries the urgency: "Έληξε 19/05" reads as a date,
        // "Έληξε 19/05 · 80 ημ" reads as a debt.
        if ($expiry === null) {
            $this->deadlineLabel = 'Χωρίς ημερομηνία ΚΤΕΟ';
        } elseif ($this->expired) {
            $this->deadlineLabel = 'Έληξε '.$expiry->format('d/m').' · '.(int) $expiry->diffInDays($today).' ημ';
        } elseif ($expiry->isSameDay($today)) {
            $this->deadlineLabel = 'Λήγει σήμερα';
        } else {
            $this->deadlineLabel = 'Λήγει '.$expiry->format('d/m').' · '.(int) $today->diffInDays($expiry).' ημ';
        }
    }

    public function render(): View
    {
        return view('components.workshop.kteo-row');
    }
}
