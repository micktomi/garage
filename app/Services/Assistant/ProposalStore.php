<?php

namespace App\Services\Assistant;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProposalStore
{
    public function create(User $user, string $actionType, string $title, array $display, array $payload): array
    {
        $token = (string) Str::uuid();

        Cache::put($this->key($token), [
            'user_id' => $user->id,
            'action_type' => $actionType,
            'payload' => $payload,
        ], now()->addSeconds((int) config('garage-assistant.proposal_ttl_seconds', 600)));

        return [
            'action_type' => $actionType,
            'title' => $title,
            'display' => $display,
            'token' => $token,
            'requires_confirmation' => true,
            'expires_in_seconds' => (int) config('garage-assistant.proposal_ttl_seconds', 600),
        ];
    }

    public function consume(User $user, string $token): array
    {
        if (! Str::isUuid($token)) {
            throw ValidationException::withMessages(['proposal' => 'Η πρόταση επιβεβαίωσης δεν είναι έγκυρη.']);
        }

        return Cache::lock($this->key($token).':lock', 5)->block(3, function () use ($user, $token): array {
            $proposal = Cache::get($this->key($token));

            if (! is_array($proposal) || (int) ($proposal['user_id'] ?? 0) !== $user->id) {
                throw ValidationException::withMessages(['proposal' => 'Η πρόταση έληξε ή δεν είναι έγκυρη.']);
            }

            Cache::forget($this->key($token));

            return $proposal;
        });
    }

    public function discard(User $user, string $token): void
    {
        if (! Str::isUuid($token)) {
            return;
        }

        $proposal = Cache::get($this->key($token));

        if (is_array($proposal) && (int) ($proposal['user_id'] ?? 0) === $user->id) {
            Cache::forget($this->key($token));
        }
    }

    private function key(string $token): string
    {
        return 'garage-assistant:proposal:'.$token;
    }
}
