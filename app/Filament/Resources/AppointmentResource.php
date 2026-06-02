<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Filament\Resources\AppointmentResource\RelationManagers;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Ραντεβού';

    protected static ?string $modelLabel = 'Ραντεβού';

    protected static ?string $pluralModelLabel = 'Ραντεβού';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Πελάτης')
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('vehicle_id')
                    ->label('Όχημα')
                    ->relationship('vehicle', 'plate_number', fn (Builder $query, Forms\Get $get) => 
                        $query->when($get('customer_id'), fn ($q) => $q->where('customer_id', $get('customer_id')))
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('appointment_date')
                    ->label('Ημερομηνία / Ώρα')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Κατάσταση')
                    ->options([
                        'scheduled' => 'Προγραμματισμένο',
                        'in_progress' => 'Σε εξέλιξη',
                        'completed' => 'Ολοκληρώθηκε',
                        'cancelled' => 'Ακυρώθηκε',
                    ])
                    ->default('scheduled')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Περιγραφή προβλήματος')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Πελάτης')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Όχημα')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('appointment_date')
                    ->label('Ημερομηνία / Ώρα')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Κατάσταση')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Προγραμματισμένο',
                        'in_progress' => 'Σε εξέλιξη',
                        'completed' => 'Ολοκληρώθηκε',
                        'cancelled' => 'Ακυρώθηκε',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('appointment_date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
