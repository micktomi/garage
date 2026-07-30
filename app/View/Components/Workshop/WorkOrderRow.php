<?php

namespace App\View\Components\Workshop;

use App\Models\WorkOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class WorkOrderRow extends Component
{
    public readonly string $customerName;

    public readonly string $plate;

    public readonly string $meta;

    public readonly string $createdLabel;

    public readonly ?string $createdDateTime;

    public function __construct(public readonly WorkOrder $order)
    {
        $vehicleName = Str::squish(trim(($order->vehicle?->make ?? '').' '.($order->vehicle?->model ?? '')));
        $description = Str::squish((string) $order->problem_description);

        $this->customerName = $order->customer?->full_name ?: 'Χωρίς πελάτη';
        $this->plate = $order->vehicle?->plate_number ?: '—';
        $this->meta = implode(' · ', array_filter([
            $vehicleName ?: 'Όχημα χωρίς στοιχεία',
            $description ?: 'Χωρίς περιγραφή εργασίας',
        ]));
        $this->createdLabel = $order->created_at?->locale('el')->diffForHumans() ?: 'Χωρίς ημερομηνία';
        $this->createdDateTime = $order->created_at?->toIso8601String();
    }

    public function render(): View
    {
        return view('components.workshop.work-order-row');
    }
}
