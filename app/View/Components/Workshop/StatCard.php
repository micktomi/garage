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
        public readonly string $icon = 'clipboard',
        public readonly string $accent = 'blue',
    ) {
        if (! in_array($tone, ['default', 'danger'], true)) {
            throw new InvalidArgumentException("Unsupported workshop stat tone [{$tone}].");
        }

        if (! in_array($icon, ['clipboard', 'calendar', 'clock', 'parts'], true)) {
            throw new InvalidArgumentException("Unsupported workshop stat icon [{$icon}].");
        }

        if (! in_array($accent, ['blue', 'violet', 'amber', 'rose'], true)) {
            throw new InvalidArgumentException("Unsupported workshop stat accent [{$accent}].");
        }
    }

    public function render(): View
    {
        return view('components.workshop.stat-card');
    }
}
