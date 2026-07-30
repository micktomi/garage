<?php

namespace App\View\Components\Workshop;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

class StatCard extends Component
{
    public function __construct(
        public readonly string $label,
        public readonly int|string $value,
        public readonly string $tone = 'default',
    ) {
        if (! in_array($tone, ['default', 'danger'], true)) {
            throw new InvalidArgumentException("Unsupported workshop stat tone [{$tone}].");
        }
    }

    public function render(): View
    {
        return view('components.workshop.stat-card');
    }
}
