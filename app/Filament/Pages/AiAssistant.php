<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Assistant\AssistantEngine;
use App\Services\Assistant\DTOs\AssistantResponse;
use App\Services\Assistant\ProposalExecutor;
use App\Services\Assistant\ProposalStore;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiAssistant extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'AI Βοηθός';

    protected static ?string $title = 'AI Βοηθός';

    protected static ?string $slug = 'ai-assistant';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.ai-assistant';

    public string $input = '';

    public array $messages = [];

    public ?array $currentAmbiguity = null;

    public ?array $currentProposal = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canAccessPanel(Filament::getPanel('admin'));
    }

    public function mount(): void
    {
        $this->messages = [[
            'role' => 'model',
            'include_in_history' => false,
            'type' => 'direct_answer',
            'message' => 'Γεια σου! Μπορώ να αναζητήσω πελάτες και οχήματα, να δείξω ιστορικό, ραντεβού και ανοιχτές εντολές ή να προετοιμάσω νέα εγγραφή για επιβεβαίωση.',
        ]];
    }

    public function sendMessage(AssistantEngine $engine, ProposalStore $proposals): void
    {
        $message = trim($this->input);

        if ($message === '') {
            $this->addResponse(AssistantResponse::error('Γράψε πρώτα ένα μήνυμα.'));

            return;
        }

        $history = collect($this->messages)
            ->filter(fn (array $item) => ($item['include_in_history'] ?? true) && in_array($item['role'] ?? null, ['user', 'model'], true))
            ->take(-12)
            ->map(fn (array $item) => [
                'role' => $item['role'],
                'text' => (string) ($item['message'] ?? ''),
            ])
            ->values()
            ->all();

        $this->messages[] = ['role' => 'user', 'type' => 'direct_answer', 'message' => $message];
        $this->input = '';
        $this->currentAmbiguity = null;
        $this->discardCurrentProposal($proposals);
        $this->currentProposal = null;

        $response = $engine->respond($message, $history, auth()->user(), request()->ip() ?? 'unknown');
        $this->addResponse($response);
    }

    public function selectAmbiguity(int $index): void
    {
        $option = $this->currentAmbiguity['data']['options'][$index] ?? null;

        if (! is_array($option) || ! is_string($option['selection_prompt'] ?? null)) {
            $this->addResponse(AssistantResponse::error('Η επιλογή δεν είναι έγκυρη.'));

            return;
        }

        $this->input = $option['selection_prompt'];
    }

    public function confirmProposal(ProposalExecutor $executor): void
    {
        $token = $this->currentProposal['proposal']['token'] ?? null;

        if (! is_string($token)) {
            $this->addResponse(AssistantResponse::error('Δεν υπάρχει έγκυρη πρόταση προς επιβεβαίωση.'));

            return;
        }

        try {
            $response = $executor->execute(auth()->user(), $token);
            $this->currentProposal = null;
            $this->addResponse($response);
        } catch (ValidationException $exception) {
            $this->addResponse(AssistantResponse::error(
                collect($exception->errors())->flatten()->first() ?: 'Η επιβεβαίωση απέτυχε.'
            ));
        } catch (Throwable) {
            $this->addResponse(AssistantResponse::error('Η επιβεβαίωση απέτυχε με ασφάλεια. Δεν δημιουργήθηκε εγγραφή.'));
        }
    }

    public function cancelProposal(ProposalStore $proposals): void
    {
        $token = $this->currentProposal['proposal']['token'] ?? null;

        if (is_string($token)) {
            $proposals->discard(auth()->user(), $token);
        }

        $this->currentProposal = null;
        $this->messages[] = [
            'role' => 'model',
            'type' => 'direct_answer',
            'message' => 'Η πρόταση ακυρώθηκε. Δεν έγινε καμία εγγραφή.',
        ];
    }

    public function clearChat(ProposalStore $proposals): void
    {
        $this->input = '';
        $this->currentAmbiguity = null;
        $this->discardCurrentProposal($proposals);
        $this->currentProposal = null;
        $this->mount();
    }

    private function addResponse(AssistantResponse $response): void
    {
        $item = ['role' => 'model', ...$response->toArray()];
        $this->messages[] = $item;

        if ($response->type === 'ambiguity') {
            $this->currentAmbiguity = $item;
        }

        if ($response->type === 'confirmation_required') {
            $this->currentProposal = $item;
        }
    }

    private function discardCurrentProposal(ProposalStore $proposals): void
    {
        $token = $this->currentProposal['proposal']['token'] ?? null;

        if (is_string($token)) {
            $proposals->discard(auth()->user(), $token);
        }
    }
}
