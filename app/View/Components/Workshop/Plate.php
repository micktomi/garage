<?php

namespace App\View\Components\Workshop;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

class Plate extends Component
{
    public function __construct(
        public readonly string $value,
        public readonly string $size = 'md',
    ) {
        if (! in_array($size, ['sm', 'md', 'lg'], true)) {
            throw new InvalidArgumentException("Unsupported workshop plate size [{$size}].");
        }
    }

    public function render(): View
    {
        return view('components.workshop.plate');
    }
}
