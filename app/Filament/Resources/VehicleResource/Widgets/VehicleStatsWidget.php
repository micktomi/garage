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

        return [
            Stat::make('Σύνολο Εργασιών', $this->record->workOrders()->count())
                ->icon('heroicon-m-clipboard-document-list'),

            Stat::make('Συνολικό Κόστος', number_format($this->record->workOrders()->sum('total_cost'), 2) . ' €')
                ->icon('heroicon-m-currency-euro'),

            Stat::make('Τελευταία Επίσκεψη', $this->record->workOrders()->latest()->first()?->created_at?->format('d/m/Y') ?? '-')
                ->icon('heroicon-m-calendar-days'),

            Stat::make('Ανοιχτές Εργασίες', $this->record->workOrders()->whereIn('status', [
                'new',
                'in_progress',
            ])->count())
                ->icon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
        ];
    }
}
