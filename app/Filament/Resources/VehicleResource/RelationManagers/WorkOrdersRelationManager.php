<?php

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use App\Enums\WorkOrderStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $title = 'Εντολές Εργασίας';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $vehicle = $this->getOwnerRecord();

                        $data['vehicle_id'] = $vehicle->getKey();
                        $data['customer_id'] = $vehicle->customer_id;

                        return $data;
                    }),
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
