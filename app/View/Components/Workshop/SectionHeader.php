<?php

namespace App\View\Components\Workshop;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SectionHeader extends Component
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $href = null,
        public readonly ?string $link = null,
    ) {}

    public function render(): View
    {
        return view('components.workshop.section-header');
    }
}
