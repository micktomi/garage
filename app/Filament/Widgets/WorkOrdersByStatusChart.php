<?php

namespace App\Filament\Widgets;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class WorkOrdersByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Κατάσταση Εργασιών';

    protected function getData(): array
    {
        $statuses = collect(WorkOrderStatus::cases());

        return [
            'datasets' => [
                [
                    'label' => 'Εντολές εργασίας',
                    'data' => $statuses
                        ->map(fn (WorkOrderStatus $status): int => WorkOrder::where('status', $status->value)->count())
                        ->all(),
                    'backgroundColor' => ['#60a5fa', '#3b82f6', '#f59e0b', '#22c55e', '#6b7280', '#ef4444'],
                ],
            ],
            'labels' => $statuses->map->label()->all(),
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
