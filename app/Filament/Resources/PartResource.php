<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartResource\Pages;
use App\Filament\Resources\PartResource\RelationManagers;
use App\Models\Part;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class PartResource extends Resource
{
    protected static ?string $model = Part::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Ανταλλακτικά';

    protected static ?string $modelLabel = 'Ανταλλακτικό';

    protected static ?string $pluralModelLabel = 'Ανταλλακτικά';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Κωδικός')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('Όνομα')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Περιγραφή')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Ποσότητα')
                    ->numeric()
                    ->default(0)
                    ->required(),
                // Visible to everyone, writable by the owner: the counter
                // needs to read a price, it does not set one. Filament does
                // not dehydrate disabled fields, so a tampered Livewire
                // payload cannot smuggle a value past this either.
                Forms\Components\TextInput::make('purchase_price')
                    ->label('Τιμή αγοράς')
                    ->numeric()
                    ->prefix('€')
                    ->default(0)
                    ->disabled(fn (): bool => Gate::denies('administer-pricing')),
                Forms\Components\TextInput::make('sale_price')
                    ->label('Τιμή πώλησης')
                    ->numeric()
                    ->prefix('€')
                    ->default(0)
                    ->disabled(fn (): bool => Gate::denies('administer-pricing')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Κωδικός')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Όνομα')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Ποσότητα')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state <= 5 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Τιμή αγοράς')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Τιμή πώλησης')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListParts::route('/'),
            'create' => Pages\CreatePart::route('/create'),
            'edit' => Pages\EditPart::route('/{record}/edit'),
        ];
    }
}
