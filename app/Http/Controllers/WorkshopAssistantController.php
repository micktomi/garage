<?php

namespace App\Http\Controllers;

use App\Services\Assistant\AssistantEngine;
use App\Services\Assistant\DTOs\AssistantResponse;
use App\Services\Assistant\ProposalExecutor;
use App\Services\Assistant\ProposalStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class WorkshopAssistantController extends Controller
{
    private function key(Request $request): string
    {
        return 'workshop_assistant.'.$request->user()->id;
    }

    private function state(Request $request): array
    {
        return $request->session()->get($this->key($request), [
            'messages' => [], 'proposal' => null, 'ambiguity' => null,
        ]);
    }

    public function show(Request $request)
    {
        return Inertia::render('Assistant/Index', [
            ...$this->state($request),
            'enabled' => (bool) config('garage-assistant.enabled') && filled(config('garage-assistant.gemini.api_key')),
        ]);
    }

    public function update(Request $request, AssistantEngine $engine, ProposalStore $store, ProposalExecutor $executor)
    {
        $input = $request->validate([
            'action' => ['required', Rule::in(['send', 'confirm', 'cancel', 'clear'])],
            'message' => ['required_if:action,send', 'nullable', 'string', 'max:2000'],
        ]);
        $state = $this->state($request);
        $token = $state['proposal']['token'] ?? null;
        if ($input['action'] === 'confirm') {
            if (! is_string($token)) {
                throw ValidationException::withMessages(['proposal' => 'Δεν υπάρχει ενεργή πρόταση προς επιβεβαίωση.']);
            }
            try {
                $response = $executor->execute($request->user(), $token);
            } catch (ValidationException $exception) {
                $response = AssistantResponse::error(collect($exception->errors())->flatten()->first());
            } catch (Throwable) {
                $response = AssistantResponse::error('Η επιβεβαίωση απέτυχε. Ελέγξτε τις εγγραφές πριν δοκιμάσετε ξανά.');
            }
            $state['proposal'] = null;
        } else {
            if (is_string($token)) {
                $store->discard($request->user(), $token);
            }
            $state['proposal'] = null;
            $state['ambiguity'] = null;
            if ($input['action'] === 'clear') {
                $request->session()->forget($this->key($request));

                return to_route('workshop.assistant');
            }
            if ($input['action'] === 'cancel') {
                $response = AssistantResponse::direct('Η πρόταση ακυρώθηκε. Δεν έγινε καμία εγγραφή.');
            } else {
                $history = collect($state['messages'])->filter(fn ($m) => in_array($m['role'], ['user', 'model']))
                    ->take(-12)->map(fn ($m) => ['role' => $m['role'], 'text' => $m['message']])->values()->all();
                $state['messages'][] = ['role' => 'user', 'message' => trim($input['message']), 'type' => 'direct_answer'];
                $response = $engine->respond(trim($input['message']), $history, $request->user(), $request->ip() ?? 'unknown');
            }
        }
        $state['messages'][] = ['role' => 'model', ...$response->toArray()];
        $state['messages'] = array_slice($state['messages'], -24);
        if ($response->type === 'confirmation_required') {
            $state['proposal'] = $response->proposal;
        }
        if ($response->type === 'ambiguity') {
            $state['ambiguity'] = $response->data;
        }
        $request->session()->put($this->key($request), $state);

        return to_route('workshop.assistant');
    }
}
