<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AppointmentResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\VehicleResource;
use App\Filament\Resources\WorkOrderResource;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GarageStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();

        return [
            Stat::make('Πελάτες', Customer::count())
                ->description('Σύνολο πελατών')
                ->icon('heroicon-o-users')
                ->url(CustomerResource::getUrl('index')),

            Stat::make('Σύνολο οχημάτων', Vehicle::count())
                ->description('Καταχωρημένα οχήματα')
                ->icon('heroicon-o-truck')
                ->url(VehicleResource::getUrl('index')),

            Stat::make('Ανοιχτές εντολές', WorkOrder::whereIn('status', ['new', 'in_progress'])->count())
                ->description('Νέες και σε εξέλιξη')
                ->color('warning')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(WorkOrderResource::getUrl('index')),

            Stat::make('Ραντεβού σήμερα', $todayAppointments)
                ->description($todayAppointments === 0 ? 'Προσθήκη ραντεβού' : 'Για σήμερα')
                ->descriptionColor($todayAppointments === 0 ? 'primary' : 'gray')
                ->descriptionIcon($todayAppointments === 0 ? 'heroicon-m-plus-circle' : null, IconPosition::Before)
                ->icon('heroicon-o-calendar-days')
                ->url($todayAppointments === 0 ? AppointmentResource::getUrl('create') : AppointmentResource::getUrl('index')),

            Stat::make('Ολοκληρωμένες εντολές', WorkOrder::where('status', 'completed')->count())
                ->description('Συνολικά')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->url(WorkOrderResource::getUrl('index')),

            Stat::make('Ανταλλακτικά σε απόθεμα', (int) Part::sum('quantity'))
                ->description('Τεμάχια')
                ->icon('heroicon-o-cog-6-tooth'),
        ];
    }
}
