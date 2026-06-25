<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Filament\Resources\WorkOrderResource\RelationManagers;
use App\Models\Part;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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

                Forms\Components\Section::make('Ανταλλακτικά')
                    ->schema([
                        Forms\Components\Repeater::make('workOrderParts')
                            ->label('Λίστα Ανταλλακτικών')
                            ->relationship()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $repeaterItems = $get('workOrderParts') ?? [];
                                $totalPartsCost = 0;

                                foreach ($repeaterItems as $item) {
                                    $totalPartsCost += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
                                }

                                $set('parts_cost', $totalPartsCost);
                                
                                $laborCost = (float) ($get('labor_cost') ?? 0);
                                $set('total_cost', $totalPartsCost + $laborCost);
                            })
                            ->schema([
                                Forms\Components\Select::make('source')
                                    ->label('Προέλευση')
                                    ->options([
                                        'from_stock'        => 'Από απόθεμα',
                                        'customer_supplied' => 'Έφερε ο πελάτης',
                                        'purchased_for_job' => 'Αγοράστηκε για τη δουλειά',
                                    ])
                                    ->default('from_stock')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        $set('part_id', null);
                                        if ($state === 'customer_supplied') {
                                            $set('unit_price', 0);
                                            $set('unit_cost', 0);
                                            $set('line_total', 0);
                                        }
                                    }),
                                Forms\Components\Select::make('part_id')
                                    ->label('Ανταλλακτικό αποθέματος')
                                    ->options(Part::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->reactive()
                                    ->visible(fn (Forms\Get $get) => $get('source') === 'from_stock')
                                    ->required(fn (Forms\Get $get) => $get('source') === 'from_stock')
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $part = Part::find($state);
                                        if ($part) {
                                            $set('unit_price', $part->sale_price);
                                            $set('unit_cost', $part->purchase_price);
                                            $set('quantity', 1);
                                            $set('line_total', $part->sale_price);
                                            $set('description', $part->name);
                                        }
                                    }),
                                Forms\Components\TextInput::make('description')
                                    ->label('Περιγραφή')
                                    ->required(fn (Forms\Get $get) => $get('source') !== 'from_stock')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Ποσότητα')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $unitPrice = (float) $get('unit_price');
                                        $set('line_total', (float) $state * $unitPrice);
                                    })
                                    ->rule(function (Forms\Get $get) {
                                        return function (string $attribute, $value, $fail) use ($get) {
                                            if ($get('source') !== 'from_stock') return;
                                            $partId = $get('part_id');
                                            if (!$partId) return;
                                            $part = Part::find($partId);
                                            if (!$part) return;
                                            if ($value > $part->quantity) {
                                                $fail("Ανεπαρκές απόθεμα. Διαθέσιμο: {$part->quantity}");
                                            }
                                        };
                                    }),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Κόστος αγοράς')
                                    ->numeric()
                                    ->prefix('€')
                                    ->nullable(),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Τιμή πώλησης')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0)
                                    ->required(fn (Forms\Get $get) => $get('source') !== 'customer_supplied')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $quantity = (float) $get('quantity');
                                        $set('line_total', (float) $state * $quantity);
                                    }),
                                Forms\Components\TextInput::make('line_total')
                                    ->label('Σύνολο γραμμής')
                                    ->numeric()
                                    ->prefix('€')
                                    ->readOnly()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('note')
                                    ->label('Σημείωση')
                                    ->maxLength(255),
                            ])
                            ->columns(3),
                    ]),

                Forms\Components\Section::make('Στοιχεία Service')
                    ->schema([
                        Forms\Components\TextInput::make('current_mileage')
                            ->label('Τρέχοντα χιλιόμετρα')
                            ->numeric()
                            ->suffix('km'),
                        Forms\Components\DatePicker::make('next_service_date')
                            ->label('Ημερομηνία επόμενου service'),
                        Forms\Components\TextInput::make('next_service_mileage')
                            ->label('Χιλιόμετρα επόμενου service')
                            ->numeric()
                            ->suffix('km'),
                    ])->columns(3),

                Forms\Components\Section::make('Κόστος & Κατάσταση')
                    ->schema([
                        Forms\Components\TextInput::make('labor_cost')
                            ->label('Κόστος εργασίας')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $set('total_cost', (float) $get('labor_cost') + (float) $get('parts_cost'));
                            }),
                        Forms\Components\TextInput::make('parts_cost')
                            ->label('Κόστος ανταλλακτικών')
                            ->numeric()
                            ->prefix('€')
                            ->default(0)
                            ->readOnly(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Εντολή Εργασίας')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.full_name')
                                    ->label('Πελάτης'),
                                Infolists\Components\TextEntry::make('vehicle.plate_number')
                                    ->label('Πινακίδα')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('status')
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
                            ]),
                        Infolists\Components\TextEntry::make('problem_description')
                            ->label('Περιγραφή προβλήματος')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('diagnosis')
                            ->label('Διάγνωση')
                            ->placeholder('Δεν έχει καταχωρηθεί')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('work_performed')
                            ->label('Εργασίες που εκτελέστηκαν')
                            ->placeholder('Δεν έχει καταχωρηθεί')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                Infolists\Components\Section::make('Στοιχεία Service')
                    ->schema([
                        Infolists\Components\TextEntry::make('current_mileage')
                            ->label('Τρέχοντα χιλιόμετρα')
                            ->suffix(' km'),
                        Infolists\Components\TextEntry::make('next_service_date')
                            ->label('Ημερομηνία επόμενου service')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('next_service_mileage')
                            ->label('Χιλιόμετρα επόμενου service')
                            ->suffix(' km'),
                    ])
                    ->visible(fn (WorkOrder $record): bool => filled($record->current_mileage) || filled($record->next_service_date) || filled($record->next_service_mileage))
                    ->columns(3),
                Infolists\Components\Section::make('Κόστος')
                    ->schema([
                        Infolists\Components\TextEntry::make('labor_cost')
                            ->label('Κόστος εργασίας')
                            ->money('EUR'),
                        Infolists\Components\TextEntry::make('parts_cost')
                            ->label('Κόστος ανταλλακτικών')
                            ->money('EUR'),
                        Infolists\Components\TextEntry::make('total_cost')
                            ->label('Συνολικό κόστος')
                            ->money('EUR')
                            ->weight('bold'),
                    ])
                    ->columns(3),
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
                Tables\Actions\Action::make('sms_ready')
                    ->label('SMS Έτοιμο')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->modalHeading('SMS — Έτοιμο για παραλαβή')
                    ->modalContent(fn (WorkOrder $record) => view('filament.sms-modal', [
                        'phone'   => $record->customer?->phone,
                        'message' => "Καλησπέρα σας. Το όχημά σας με πινακίδα {$record->vehicle?->plate_number} είναι έτοιμο για παραλαβή από το συνεργείο.",
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Κλείσιμο')
                    ->visible(fn (WorkOrder $record): bool => filled($record->customer?->phone)),
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
