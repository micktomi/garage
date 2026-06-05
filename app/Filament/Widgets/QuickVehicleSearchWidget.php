<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class QuickVehicleSearchWidget extends BaseWidget implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $heading = 'Γρήγορη αναζήτηση οχήματος / πελάτη';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Vehicle::query()
                    ->with('customer')
            )
            ->modifyQueryUsing(function (Builder $query) {
                $search = $this->tableSearch;
                if (strlen($search) < 2) {
                    return $query->whereRaw('1 = 0');
                }
                return $query;
            })
            ->columns([
                TextColumn::make('plate_number')
                    ->label('Πινακίδα')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('make')
                    ->label('Μάρκα')
                    ->searchable(),
                TextColumn::make('model')
                    ->label('Μοντέλο')
                    ->searchable(),
                TextColumn::make('customer.full_name')
                    ->label('Πελάτης')
                    ->searchable(),
                TextColumn::make('customer.phone')
                    ->label('Τηλέφωνο')
                    ->searchable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Προβολή')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Vehicle $record): string => \App\Filament\Resources\VehicleResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(fn () => strlen($this->tableSearch) < 2 
                ? 'Πληκτρολογήστε τουλάχιστον 2 χαρακτήρες για αναζήτηση' 
                : 'Δεν βρέθηκαν αποτελέσματα')
            ->searchPlaceholder('Πινακίδα, Μάρκα, Μοντέλο, Πελάτης ή Τηλέφωνο...')
            ->searchDebounce('500ms');
    }
}
