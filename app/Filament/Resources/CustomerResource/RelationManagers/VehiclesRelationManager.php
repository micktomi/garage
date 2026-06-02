<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    protected static ?string $title = 'Οχήματα';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('plate_number')
                    ->label('Πινακίδα')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('make')
                    ->label('Μάρκα')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('model')
                    ->label('Μοντέλο')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plate_number')
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Πινακίδα')
                    ->searchable(),
                Tables\Columns\TextColumn::make('make')
                    ->label('Μάρκα')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Μοντέλο')
                    ->searchable(),
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
