<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Οχήματα';

    protected static ?string $modelLabel = 'Όχημα';

    protected static ?string $pluralModelLabel = 'Οχήματα';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Πελάτης')
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('plate_number')
                    ->label('Πινακίδα')
                    ->required()
                    ->maxLength(255),
                Select::make('make')
                    ->label('Μάρκα')
                    ->options(fn () => VehicleModel::query()
                        ->distinct()
                        ->orderBy('make')
                        ->pluck('make', 'make'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('model', null))
                    ->required(),
                TextInput::make('model')
                    ->label('Μοντέλο')
                    ->datalist(fn (Get $get) => VehicleModel::query()
                        ->where('make', $get('make'))
                        ->orderBy('model')
                        ->pluck('model')
                        ->all())
                    ->disabled(fn (Get $get) => blank($get('make')))
                    ->required(),
                Forms\Components\TextInput::make('year')
                    ->label('Έτος')
                    ->numeric(),
                Forms\Components\TextInput::make('mileage')
                    ->label('Χιλιόμετρα')
                    ->numeric(),
                Forms\Components\TextInput::make('vin')
                    ->label('Αριθμός πλαισίου')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('Σημειώσεις')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Πληροφορίες Οχήματος')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('plate_number')
                                    ->label('Πινακίδα')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('customer.full_name')
                                    ->label('Ιδιοκτήτης')
                                    ->url(fn (Vehicle $record): string => CustomerResource::getUrl('view', ['record' => $record->customer_id])),
                                Infolists\Components\TextEntry::make('mileage')
                                    ->label('Χιλιόμετρα')
                                    ->numeric()
                                    ->suffix(' km'),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('make')
                                    ->label('Μάρκα'),
                                Infolists\Components\TextEntry::make('model')
                                    ->label('Μοντέλο'),
                                Infolists\Components\TextEntry::make('year')
                                    ->label('Έτος'),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('vin')
                                    ->label('Αριθμός πλαισίου (VIN)')
                                    ->fontFamily('mono')
                                    ->copyable(),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('last_service_date')
                                    ->label('Τελευταίο service')
                                    ->state(fn (Vehicle $record): ?string => $record->latestTrackedWorkOrder()?->created_at?->format('d/m/Y'))
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('next_service_date')
                                    ->label('Επόμενο service')
                                    ->state(fn (Vehicle $record): ?string => $record->latestTrackedWorkOrder()?->next_service_date?->format('d/m/Y'))
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('next_service_mileage')
                                    ->label('Επόμενο service στα')
                                    ->state(fn (Vehicle $record): ?string => $record->latestTrackedWorkOrder()?->next_service_mileage ? number_format($record->latestTrackedWorkOrder()->next_service_mileage) . ' km' : null)
                                    ->placeholder('-'),
                            ]),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Σημειώσεις')
                            ->columnSpanFull()
                            ->placeholder('Δεν υπάρχουν σημειώσεις'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Πελάτης')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Πινακίδα')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('make')
                    ->label('Μάρκα')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Μοντέλο')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Έτος')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mileage')
                    ->label('Χιλιόμετρα')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vin')
                    ->label('Αριθμός πλαισίου')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\WorkOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
