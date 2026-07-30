<?php

namespace App\View\Components\Workshop;

use App\Enums\WorkOrderStatus;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public function __construct(public readonly WorkOrderStatus $status) {}

    public function render(): View
    {
        return view('components.workshop.status-badge');
    }
}
