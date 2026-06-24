<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WorkOrdersByStatusChart extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function updateChartData(): void
    {
        // Kept so an already-open dashboard tab with the old chart polling does not error.
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Νέες', WorkOrder::where('status', 'new')->count())
                ->description('Προς ανάθεση')
                ->color('info')
                ->icon('heroicon-o-inbox')
                ->url(WorkOrderResource::getUrl('index')),

            Stat::make('Σε εξέλιξη', WorkOrder::where('status', 'in_progress')->count())
                ->description('Στο συνεργείο')
                ->color('warning')
                ->icon('heroicon-o-wrench-screwdriver')
                ->url(WorkOrderResource::getUrl('index')),

            Stat::make('Ολοκληρωμένες', WorkOrder::where('status', 'completed')->count())
                ->description('Έτοιμες / κλειστές')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->url(WorkOrderResource::getUrl('index')),

            Stat::make('Ακυρωμένες', WorkOrder::where('status', 'cancelled')->count())
                ->description('Δεν προχωρούν')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->url(WorkOrderResource::getUrl('index')),
        ];
    }
}
