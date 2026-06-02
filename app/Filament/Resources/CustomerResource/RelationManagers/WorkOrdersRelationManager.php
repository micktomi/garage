<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $title = 'Εντολές Εργασίας';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehicle_id')
                    ->label('Όχημα')
                    ->relationship('vehicle', 'plate_number', fn (Builder $query, RelationManager $livewire) => 
                        $query->where('customer_id', $livewire->getOwnerRecord()->id)
                    )
                    ->required(),
                Forms\Components\Textarea::make('problem_description')
                    ->label('Περιγραφή προβλήματος')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Κατάσταση')
                    ->options([
                        'new' => 'Νέα',
                        'in_progress' => 'Σε εξέλιξη',
                        'completed' => 'Ολοκληρώθηκε',
                        'delivered' => 'Παραδόθηκε',
                    ])
                    ->default('new')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('problem_description')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Όχημα')
                    ->sortable(),
                Tables\Columns\TextColumn::make('problem_description')
                    ->label('Περιγραφή προβλήματος')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Κατάσταση')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'Νέα',
                        'in_progress' => 'Σε εξέλιξη',
                        'completed' => 'Ολοκληρώθηκε',
                        'delivered' => 'Παραδόθηκε',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'delivered' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ημερομηνία')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
