<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\VehicleResource;
use App\Filament\Resources\WorkOrderResource;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function customerCreateUrl(): string
    {
        return CustomerResource::getUrl('create');
    }

    public function vehicleCreateUrl(): string
    {
        return VehicleResource::getUrl('create');
    }

    public function workOrderCreateUrl(): string
    {
        return WorkOrderResource::getUrl('create');
    }
}
