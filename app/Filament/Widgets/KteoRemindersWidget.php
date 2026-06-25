<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class KteoRemindersWidget extends BaseWidget
{
    protected static ?string $heading = 'Υπενθυμίσεις ΚΤΕΟ';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = Carbon::today();
        $in30Days = Carbon::today()->addDays(30);

        return $table
            ->query(
                Vehicle::query()
                    ->with('customer')
                    ->whereNotNull('kteo_expires_at')
                    ->where('kteo_expires_at', '<=', $in30Days)
                    ->orderBy('kteo_expires_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Πινακίδα')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('make_model')
                    ->label('Μάρκα / Μοντέλο')
                    ->state(fn (Vehicle $record): string => trim("{$record->make} {$record->model}")),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Πελάτης'),

                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Τηλέφωνο')
                    ->state(fn (Vehicle $record): string => $record->customer?->phone ?? '-'),

                Tables\Columns\TextColumn::make('kteo_expires_at')
                    ->label('Λήξη ΚΤΕΟ')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('kteo_status')
                    ->label('Κατάσταση')
                    ->badge()
                    ->state(function (Vehicle $record) use ($today): string {
                        $expires = $record->kteo_expires_at;
                        if ($expires->lt($today)) {
                            return 'Έληξε';
                        }
                        if ($expires->eq($today)) {
                            return 'Λήγει σήμερα';
                        }
                        $days = $today->diffInDays($expires);
                        return "Σε {$days} ημέρες";
                    })
                    ->color(function (Vehicle $record) use ($today): string {
                        $expires = $record->kteo_expires_at;
                        if ($expires->lte($today)) {
                            return 'danger';
                        }
                        $days = $today->diffInDays($expires);
                        if ($days <= 7) {
                            return 'warning';
                        }
                        return 'gray';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('sms_kteo')
                    ->label('SMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->modalHeading('Αποστολή SMS υπενθύμισης ΚΤΕΟ')
                    ->modalContent(fn (Vehicle $record) => view('filament.sms-modal', [
                        'phone'   => $record->customer?->phone,
                        'message' => "Καλησπέρα σας. Σας υπενθυμίζουμε ότι πλησιάζει / έχει λήξει το ΚΤΕΟ για το όχημά σας με πινακίδα {$record->plate_number}. Παρακαλώ επικοινωνήστε με το συνεργείο.",
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Κλείσιμο')
                    ->visible(fn (Vehicle $record): bool => filled($record->customer?->phone)),
            ])
            ->paginated(false);
    }
}
