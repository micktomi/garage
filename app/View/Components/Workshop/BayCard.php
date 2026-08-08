<?php

namespace App\View\Components\Workshop;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Presentation-only view of an open work order as a workshop position.
 *
 * The application does not model physical bays, so nothing here is
 * persisted or derived from a bay entity — every value comes from the
 * work order that is already loaded for the dashboard.
 */
class BayCard extends Component
{
    /**
     * Stage segments drawn on the card rail: παραλαβή, διάγνωση, εργασία,
     * έτοιμο. Always four equal parts, so cards compare at a glance.
     */
    public const STAGES = 4;

    public readonly string $orderNumber;

    public readonly string $plate;

    public readonly string $vehicleName;

    public readonly string $jobLine;

    /** Full job text, but only when the single clamped line cannot hold it. */
    public readonly ?string $jobTooltip;

    public readonly string $elapsedLabel;

    /** How many of the {@see self::STAGES} segments the current status fills. */
    public readonly int $stage;

    public readonly int $stages;

    public function __construct(public readonly WorkOrder $order)
    {
        $vehicleName = Str::squish(trim(($order->vehicle?->make ?? '').' '.($order->vehicle?->model ?? '')));
        $problem = Str::squish((string) $order->problem_description);

        $this->orderNumber = str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
        $this->plate = $order->vehicle?->plate_number ?: '—';
        $this->vehicleName = $vehicleName ?: 'Όχημα χωρίς στοιχεία';
        // Customer first: on a single clamped line the name is what the owner
        // scans for, so the description is the part that may be cut.
        $this->jobLine = implode(' · ', array_filter([
            $order->customer?->full_name ?: null,
            Str::limit($problem, 60) ?: null,
        ])) ?: 'Χωρίς περιγραφή εργασίας';
        $this->jobTooltip = mb_strlen($this->jobLine) > 32 ? $this->jobLine : null;
        $this->elapsedLabel = $this->elapsed();
        $this->stages = self::STAGES;
        $this->stage = match ($order->status) {
            WorkOrderStatus::New => 1,
            WorkOrderStatus::InProgress, WorkOrderStatus::AwaitingParts => 3,
            default => self::STAGES,
        };
    }

    private function elapsed(): string
    {
        $openedAt = $this->order->created_at;

        if ($openedAt === null) {
            return 'Χωρίς ώρα παραλαβής';
        }

        $days = (int) $openedAt->diffInDays(now());
        $hours = (int) $openedAt->copy()->addDays($days)->diffInHours(now());

        if ($days >= 1) {
            return $hours >= 1
                ? "Μέσα {$days} ημ {$hours} ώρ"
                : "Μέσα {$days} ημ";
        }

        return $hours >= 1 ? "Μέσα {$hours} ώρ" : 'Μέσα λιγότερο από 1 ώρα';
    }

    public function render(): View
    {
        return view('components.workshop.bay-card');
    }
}
