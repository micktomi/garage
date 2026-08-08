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

    public readonly string $vehicleName;

    public readonly string $problem;

    public readonly string $meta;

    public readonly string $createdLabel;

    public readonly ?string $createdDateTime;

    public readonly string $dashboardDateLabel;

    /** Full text, set only where the single line actually runs out of room. */
    public readonly ?string $customerTooltip;

    public readonly ?string $metaTooltip;

    /** True when the order is still open but the vehicle has left the shop. */
    public readonly bool $outOfShop;

    public function __construct(public readonly WorkOrder $order)
    {
        $vehicleName = Str::squish(trim(($order->vehicle?->make ?? '').' '.($order->vehicle?->model ?? '')));
        $description = Str::squish((string) $order->problem_description);

        $this->customerName = $order->customer?->full_name ?: 'Χωρίς πελάτη';
        $this->plate = $order->vehicle?->plate_number ?: '—';
        $this->vehicleName = $vehicleName ?: 'Όχημα χωρίς στοιχεία';
        $this->problem = $description ?: 'Χωρίς περιγραφή εργασίας';
        $this->meta = implode(' · ', array_filter([
            $this->vehicleName,
            $this->problem,
        ]));
        $this->customerTooltip = mb_strlen($this->customerName) > 24 ? $this->customerName : null;
        $this->metaTooltip = mb_strlen($this->meta) > 40 ? $this->meta : null;
        $this->outOfShop = $order->status->isOpen() && ! $order->in_shop;
        $this->createdLabel = $order->created_at?->locale('el')->diffForHumans() ?: 'Χωρίς ημερομηνία';
        $this->createdDateTime = $order->created_at?->toIso8601String();
        $this->dashboardDateLabel = $order->created_at?->format('d/m/Y') ?: '—';
    }

    public function render(): View
    {
        return view('components.workshop.work-order-row');
    }
}
