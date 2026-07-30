<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\WorkOrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->relationship('vehicle', 'plate_number', fn (Builder $query, RelationManager $livewire) => $query->where('customer_id', $livewire->getOwnerRecord()->id)
                    )
                    ->required(),
                Forms\Components\Textarea::make('problem_description')
                    ->label('Περιγραφή προβλήματος')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Κατάσταση')
                    ->options(WorkOrderStatus::options())
                    ->default(WorkOrderStatus::New->value)
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
                    ->formatStateUsing(fn (WorkOrderStatus|string $state): string => WorkOrderStatus::resolve($state)->label())
                    ->color(fn (WorkOrderStatus|string $state): string => WorkOrderStatus::resolve($state)->filamentColor()),
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
