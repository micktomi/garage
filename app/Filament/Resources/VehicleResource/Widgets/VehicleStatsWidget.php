<?php

namespace App\Filament\Resources\VehicleResource\Widgets;

use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class VehicleStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record instanceof Vehicle) {
            return [];
        }

        $latestTrackedWorkOrder = $this->record->latestTrackedWorkOrder();

        return [
            Stat::make('Σύνολο Εργασιών', $this->record->workOrders()->count())
                ->icon('heroicon-m-clipboard-document-list'),

            Stat::make('Τελευταίο service', $latestTrackedWorkOrder?->created_at?->format('d/m/Y') ?? '-')
                ->icon('heroicon-m-calendar-days'),

            Stat::make('Επόμενο service', $latestTrackedWorkOrder?->next_service_date?->format('d/m/Y') ?? '-')
                ->icon('heroicon-m-calendar'),

            Stat::make('Επόμενο service στα', $latestTrackedWorkOrder?->next_service_mileage ? number_format($latestTrackedWorkOrder->next_service_mileage) . ' km' : '-')
                ->icon('heroicon-m-wrench-screwdriver'),
        ];
    }
}
