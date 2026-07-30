<?php

namespace App\View\Components\Workshop;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EmptyState extends Component
{
    public function __construct(
        public readonly string $icon,
        public readonly string $title,
    ) {}

    public function render(): View
    {
        return view('components.workshop.empty-state');
    }
}
