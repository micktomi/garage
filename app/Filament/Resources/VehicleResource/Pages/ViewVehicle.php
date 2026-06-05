<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use App\Filament\Resources\VehicleResource\Widgets\VehicleStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    public function getTitle(): string
    {
        return 'Καρτέλα Οχήματος';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VehicleStatsWidget::class,
        ];
    }
}
