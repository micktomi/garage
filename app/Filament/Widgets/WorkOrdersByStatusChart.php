<?php

namespace App\Filament\Widgets;

use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class WorkOrdersByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Κατάσταση Εργασιών';

    protected function getData(): array
    {
        $statuses = [
            'new' => 'Νέες',
            'in_progress' => 'Σε εξέλιξη',
            'completed' => 'Ολοκληρωμένες',
            'cancelled' => 'Ακυρωμένες',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Εντολές εργασίας',
                    'data' => collect($statuses)
                        ->keys()
                        ->map(fn (string $status): int => WorkOrder::where('status', $status)->count())
                        ->all(),
                    'backgroundColor' => ['#60a5fa', '#f59e0b', '#22c55e', '#ef4444'],
                ],
            ],
            'labels' => array_values($statuses),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
