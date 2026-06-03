<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Filament\Resources\WorkOrderResource\RelationManagers;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Εντολές Εργασίας';

    protected static ?string $modelLabel = 'Εντολή Εργασίας';

    protected static ?string $pluralModelLabel = 'Εντολές Εργασίας';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Πληροφορίες Πελάτη & Οχήματος')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Πελάτης')
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('vehicle_id', null)),
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Όχημα')
                            ->relationship('vehicle', 'plate_number', fn (Builder $query, Forms\Get $get) => 
                                $query->when($get('customer_id'), fn ($q) => $q->where('customer_id', $get('customer_id')))
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Λεπτομέρειες Εργασίας')
                    ->schema([
                        Forms\Components\Textarea::make('problem_description')
                            ->label('Περιγραφή προβλήματος')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('diagnosis')
                            ->label('Διάγνωση')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('work_performed')
                            ->label('Εργασίες που εκτελέστηκαν')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Κόστος & Κατάσταση')
                    ->schema([
                        Forms\Components\TextInput::make('labor_cost')
                            ->label('Κόστος εργασίας')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $set('total_cost', (float)$get('labor_cost') + (float)$get('parts_cost'));
                            }),
                        Forms\Components\TextInput::make('parts_cost')
                            ->label('Κόστος ανταλλακτικών')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $set('total_cost', (float)$get('labor_cost') + (float)$get('parts_cost'));
                            }),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Συνολικό κόστος')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->readOnly(),
                        Forms\Components\Select::make('status')
                            ->label('Κατάσταση')
                            ->options([
                                'new' => 'Νέα',
                                'in_progress' => 'Σε εξέλιξη',
                                'completed' => 'Ολοκληρώθηκε',
                                'cancelled' => 'Ακυρώθηκε',
                            ])
                            ->default('new')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Πελάτης')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Πινακίδα')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Συνολικό κόστος')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Κατάσταση')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'Νέα',
                        'in_progress' => 'Σε εξέλιξη',
                        'completed' => 'Ολοκληρώθηκε',
                        'cancelled' => 'Ακυρώθηκε',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ημερομηνία')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Εκτύπωση')
                    ->icon('heroicon-o-printer')
                    ->url(fn (WorkOrder $record): string => route('work-orders.print', $record))
                    ->openUrlInNewTab(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'view' => Pages\ViewWorkOrder::route('/{record}'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
