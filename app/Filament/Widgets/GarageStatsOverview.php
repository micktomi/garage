<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GarageStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Πελάτες', Customer::count())
                ->description('Σύνολο πελατών'),

            Stat::make('Οχήματα', Vehicle::count())
                ->description('Καταχωρημένα οχήματα'),

            Stat::make('Σημερινά ραντεβού', Appointment::whereDate('scheduled_at', today())->count())
                ->description('Ραντεβού σήμερα'),

            Stat::make('Ανοιχτές εργασίες', WorkOrder::where('status', 'open')->count())
                ->description('Εντολές σε εκκρεμότητα'),

            Stat::make('Χαμηλό stock', Part::whereColumn('stock', '<=', 'min_stock')->count())
                ->description('Ανταλλακτικά που θέλουν έλεγχο'),
        ];
    }
}