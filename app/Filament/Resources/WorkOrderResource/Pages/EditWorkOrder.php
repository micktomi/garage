<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource;
use App\Filament\Support\ArchiveActions;
use App\Models\WorkOrder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ArchiveActions::headerArchive(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->refuseStaffAmendmentOfFiledOrder();
        $this->refuseIllegalStatusTransition();
    }

    /**
     * Persists the form through the same atomic compare-and-swap as the
     * workshop status endpoint (WorkOrder::updateWithExpectedVersion()) —
     * not a second read-before-write. $data['lock_version'] is the form's
     * hidden token: Livewire re-resolves $record from the database on every
     * request, so the in-memory record is always current and could never by
     * itself detect a conflict; the form state is the only thing that still
     * remembers what the user was actually looking at.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $record->updateWithExpectedVersion((int) ($data['lock_version'] ?? -1), $data);
        } catch (ValidationException) {
            Notification::make()
                ->danger()
                ->persistent()
                ->title('Η εντολή άλλαξε στο μεταξύ')
                ->body('Κάποιος άλλος ενημέρωσε αυτή την εντολή ενώ ήταν ανοιχτή η φόρμα. Δεν αποθηκεύτηκε τίποτα. Ανανεώστε τη σελίδα για να δείτε την τρέχουσα κατάσταση.')
                ->send();

            $this->halt();
        }

        return $record;
    }

    /**
     * An order that is already Completed is a closed record; editing it is a
     * correction, which is an owner's call. Read from the database, not from
     * the in-memory record, for the same reason as the version check.
     */
    private function refuseStaffAmendmentOfFiledOrder(): void
    {
        $stored = WorkOrder::query()->whereKey($this->getRecord()->getKey())->first();

        if ($stored === null || Gate::allows('amendCompleted', $stored)) {
            return;
        }

        Notification::make()
            ->danger()
            ->persistent()
            ->title('Η εντολή έχει ήδη ολοκληρωθεί')
            ->body('Οι ολοκληρωμένες εντολές τροποποιούνται μόνο από τον ιδιοκτήτη. Δεν αποθηκεύτηκε τίποτα.')
            ->send();

        $this->halt();
    }

    /**
     * The Filament half of the status state machine: the select offers
     * every state, so an illegal jump — new straight to completed,
     * anything out of cancelled — is refused here, against the stored
     * status, with the same owner-reopen exception the workshop
     * endpoint honours.
     */
    private function refuseIllegalStatusTransition(): void
    {
        $stored = WorkOrder::query()->whereKey($this->getRecord()->getKey())->first();
        $next = WorkOrderStatus::tryFrom((string) ($this->data['status'] ?? ''));

        if ($stored === null || $next === null) {
            return;
        }

        if ($stored->status->canTransitionTo($next)
            || Gate::allows('reopenCompleted', [$stored, $next])) {
            return;
        }

        Notification::make()
            ->danger()
            ->persistent()
            ->title('Μη επιτρεπτή αλλαγή κατάστασης')
            ->body("Δεν επιτρέπεται η αλλαγή από «{$stored->status->label()}» σε «{$next->label()}».")
            ->send();

        $this->halt();
    }

    /**
     * Adopt the version this save produced, so a second save from the same
     * open page is not reported as a conflict with itself.
     */
    protected function afterSave(): void
    {
        $this->data['lock_version'] = $this->getRecord()->refresh()->lock_version;
    }
}
