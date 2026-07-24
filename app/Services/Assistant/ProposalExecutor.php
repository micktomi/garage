<?php

namespace App\Services\Assistant;

use App\Actions\CreateAppointmentAction;
use App\Actions\CreateWorkOrderAction;
use App\Models\User;
use App\Services\Assistant\DTOs\AssistantResponse;
use Illuminate\Validation\ValidationException;

class ProposalExecutor
{
    public function __construct(
        private readonly ProposalStore $proposals,
        private readonly CreateAppointmentAction $createAppointment,
        private readonly CreateWorkOrderAction $createWorkOrder,
    ) {}

    public function execute(User $user, string $token): AssistantResponse
    {
        $proposal = $this->proposals->consume($user, $token);

        return match ($proposal['action_type'] ?? null) {
            'create_appointment' => $this->executeAppointment($proposal['payload'] ?? [], $user),
            'create_work_order' => $this->executeWorkOrder($proposal['payload'] ?? [], $user),
            default => throw ValidationException::withMessages(['proposal' => 'Η προτεινόμενη ενέργεια δεν επιτρέπεται.']),
        };
    }

    private function executeAppointment(array $payload, User $user): AssistantResponse
    {
        $appointment = $this->createAppointment->execute($payload, $user, 'ai_assistant');

        return AssistantResponse::direct('Το ραντεβού δημιουργήθηκε μετά την επιβεβαίωσή σου.', [
            'created' => ['type' => 'appointment', 'id' => $appointment->id],
        ]);
    }

    private function executeWorkOrder(array $payload, User $user): AssistantResponse
    {
        $workOrder = $this->createWorkOrder->execute($payload, $user, 'ai_assistant');

        return AssistantResponse::direct('Η εντολή εργασίας δημιουργήθηκε μετά την επιβεβαίωσή σου.', [
            'created' => ['type' => 'work_order', 'id' => $workOrder->id],
        ]);
    }
}
