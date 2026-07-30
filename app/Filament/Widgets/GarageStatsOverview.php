<?php

namespace App\Filament\Widgets;

use App\Enums\WorkOrderStatus;
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
            Stat::make('Σύνολο πελατών', Customer::count())
                ->icon('heroicon-o-users'),

            Stat::make('Σύνολο οχημάτων', Vehicle::count())
                ->icon('heroicon-o-truck'),

            Stat::make('Ανοιχτές εντολές εργασίας', WorkOrder::whereIn('status', WorkOrderStatus::openValues())->count())
                ->description('Σε εξέλιξη')
                ->color('warning')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('Σημερινά ραντεβού', Appointment::whereDate('appointment_date', today())->count())
                ->description('Για σήμερα')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Ολοκληρωμένες εντολές', WorkOrder::where('status', WorkOrderStatus::Completed->value)->count())
                ->description('Συνολικά')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Ανταλλακτικά σε απόθεμα', (int) Part::sum('quantity'))
                ->description('Τεμάχια')
                ->icon('heroicon-o-cog-6-tooth'),
        ];
    }
}
