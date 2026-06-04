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

            Stat::make('Σημερινά ραντεβού', Appointment::whereDate('appointment_date', today())->count())
                ->description('Ραντεβού σήμερα'),

            Stat::make('Ανοιχτές εργασίες', WorkOrder::whereIn('status', [
                'new',
                'in_progress',
            ])->count())
                ->description('Νέες ή σε εξέλιξη εντολές'),

            Stat::make('Χαμηλό stock', Part::where('quantity', '<=', 3)->count())
                ->description('Ανταλλακτικά με ποσότητα έως 3'),
        ];
    }
}
