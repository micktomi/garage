<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VehicleResource;
use App\Models\Vehicle;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class QuickVehicleSearchWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-vehicle-search-widget';

    protected static ?string $heading = 'Γρήγορη αναζήτηση οχήματος / πελάτη';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public ?string $search = null;

    public function getResults(): Collection
    {
        $search = trim($this->search ?? '');

        if (mb_strlen($search) < 2) {
            return new Collection();
        }

        return Vehicle::query()
            ->with('customer')
            ->where(function ($query) use ($search) {
                $query
                    ->where('plate_number', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->limit(8)
            ->get();
    }

    public function vehicleUrl(Vehicle $vehicle): string
    {
        return VehicleResource::getUrl('view', ['record' => $vehicle]);
    }
}
