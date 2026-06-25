<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sms_ready')
                ->label('SMS Έτοιμο')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->modalHeading('SMS — Έτοιμο για παραλαβή')
                ->modalContent(fn () => view('filament.sms-modal', [
                    'phone'   => $this->record->customer?->phone,
                    'message' => "Καλησπέρα σας. Το όχημά σας με πινακίδα {$this->record->vehicle?->plate_number} είναι έτοιμο για παραλαβή από το συνεργείο.",
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Κλείσιμο')
                ->visible(fn (): bool => filled($this->record->customer?->phone)),
            Actions\EditAction::make(),
        ];
    }
}
