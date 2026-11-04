<?php

namespace App\Filament\Resources\WorkOrderResource\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\WorkOrderStatus;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Payments of one work order. Recorded through PaymentService (no
 * overpayment, not on cancelled orders); never edited; deleted only by the
 * owner (PaymentPolicy), which is written to the activity log.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Πληρωμές';

    protected static ?string $modelLabel = 'πληρωμή';

    protected static ?string $pluralModelLabel = 'πληρωμές';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('amount')
                ->label('Ποσό')
                ->numeric()
                ->prefix('€')
                ->required()
                ->minValue(0.01)
                ->default(fn (RelationManager $livewire) => max(0, $livewire->getOwnerRecord()->balance()))
                ->rule(fn (RelationManager $livewire) => function (string $attribute, $value, $fail) use ($livewire) {
                    $balance = $livewire->getOwnerRecord()->balanceCents();
                    if ((int) round((float) $value * 100) > $balance) {
                        $fail('Το ποσό υπερβαίνει το υπόλοιπο της εντολής ('.PaymentService::money(max(0, $balance)).').');
                    }
                }),
            Forms\Components\Select::make('method')
                ->label('Τρόπος')
                ->options(PaymentMethod::options())
                ->default(PaymentMethod::Cash->value)
                ->required(),
            Forms\Components\DateTimePicker::make('paid_at')
                ->label('Ημερομηνία')
                ->default(now())
                ->required(),
            Forms\Components\TextInput::make('note')
                ->label('Σημείωση')
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->defaultSort('paid_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')->label('Ημερομηνία')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('amount')->label('Ποσό')->money('EUR'),
                Tables\Columns\TextColumn::make('method')->label('Τρόπος')
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                Tables\Columns\TextColumn::make('user.name')->label('Καταχώρισε'),
                Tables\Columns\TextColumn::make('note')->label('Σημείωση'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Καταχώριση πληρωμής')
                    ->visible(fn (RelationManager $livewire) => $livewire->getOwnerRecord()->status !== WorkOrderStatus::Cancelled)
                    ->using(fn (array $data, RelationManager $livewire): Model => app(PaymentService::class)->record(
                        $livewire->getOwnerRecord(),
                        (float) $data['amount'],
                        PaymentMethod::from($data['method']),
                        Carbon::parse($data['paid_at']),
                        $data['note'] ?? null,
                    )),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Η πληρωμή διαγράφεται και καταγράφεται στο ιστορικό αλλαγών.'),
            ]);
    }

    /** Also usable from the view page: recording a payment is not editing the order. */
    public function isReadOnly(): bool
    {
        return false;
    }
}
