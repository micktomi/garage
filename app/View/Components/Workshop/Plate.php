<?php

namespace App\View\Components\Workshop;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Plate extends Component
{
    public function __construct(public readonly string $value) {}

    public function render(): View
    {
        return view('components.workshop.plate');
    }
}
