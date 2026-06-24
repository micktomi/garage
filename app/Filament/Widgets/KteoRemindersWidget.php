<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VehicleResource;
use App\Models\Vehicle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class KteoRemindersWidget extends BaseWidget
{
    protected static ?string $heading = 'Υπενθυμίσεις ΚΤΕΟ — ληγμένα και επόμενες 30 ημέρες';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = Carbon::today();
        $in7Days = Carbon::today()->addDays(7);
        $in30Days = Carbon::today()->addDays(30);

        return $table
            ->query(
                Vehicle::query()
                    ->with('customer')
                    ->whereNotNull('kteo_expires_at')
                    ->where('kteo_expires_at', '<=', $in30Days)
                    ->orderByRaw(
                        'case when kteo_expires_at < ? then 0 when kteo_expires_at <= ? then 1 else 2 end',
                        [$today->toDateString(), $in7Days->toDateString()],
                    )
                    ->orderBy('kteo_expires_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Όχημα')
                    ->weight('bold')
                    ->description(fn (Vehicle $record): string => trim("{$record->make} {$record->model}") ?: '-')
                    ->url(fn (Vehicle $record): string => VehicleResource::getUrl('view', ['record' => $record]))
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Πελάτης')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Τηλέφωνο')
                    ->state(fn (Vehicle $record): ?string => $record->customer?->phone)
                    ->placeholder('-')
                    ->url(fn (Vehicle $record): ?string => $this->phoneUrl($record, 'tel'))
                    ->openUrlInNewTab(false),

                Tables\Columns\TextColumn::make('kteo_expires_at')
                    ->label('Λήξη')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('kteo_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Vehicle $record): string => $this->kteoStatusLabel($record, $today))
                    ->color(fn (Vehicle $record): string => $this->kteoStatusColor($record, $today)),
            ])
            ->actions([
                Tables\Actions\Action::make('message')
                    ->label('Μήνυμα')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->url(fn (Vehicle $record): ?string => $this->phoneUrl($record, 'sms'))
                    ->visible(fn (Vehicle $record): bool => filled($this->cleanPhone($record->customer?->phone)))
                    ->openUrlInNewTab(false),
            ])
            ->striped()
            ->paginated(false);
    }

    protected function getTableDescription(): ?string
    {
        $today = Carbon::today();
        $in7Days = Carbon::today()->addDays(7);
        $in30Days = Carbon::today()->addDays(30);

        $expired = Vehicle::query()
            ->whereNotNull('kteo_expires_at')
            ->where('kteo_expires_at', '<', $today)
            ->count();

        $urgent = Vehicle::query()
            ->whereNotNull('kteo_expires_at')
            ->whereBetween('kteo_expires_at', [$today, $in7Days])
            ->where('kteo_expires_at', '<=', $in30Days)
            ->count();

        return sprintf(
            '%d %s · %d %s',
            $expired,
            $expired === 1 ? 'ληγμένο' : 'ληγμένα',
            $urgent,
            $urgent === 1 ? 'επείγον' : 'επείγοντα',
        );
    }

    private function kteoStatusLabel(Vehicle $vehicle, Carbon $today): string
    {
        $expires = $vehicle->kteo_expires_at;

        if ($expires->lt($today)) {
            return 'Ληγμένο';
        }

        if ($today->diffInDays($expires) <= 7) {
            return 'Επείγον';
        }

        return 'Σύντομα';
    }

    private function kteoStatusColor(Vehicle $vehicle, Carbon $today): string
    {
        $expires = $vehicle->kteo_expires_at;

        if ($expires->lt($today)) {
            return 'danger';
        }

        if ($today->diffInDays($expires) <= 7) {
            return 'warning';
        }

        return 'gray';
    }

    private function phoneUrl(Vehicle $vehicle, string $scheme): ?string
    {
        $phone = $this->cleanPhone($vehicle->customer?->phone);

        return $phone ? "{$scheme}:{$phone}" : null;
    }

    private function cleanPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $phone = preg_replace('/(?!^)\+|[^0-9+]/', '', $phone);

        return filled($phone) ? $phone : null;
    }
}
