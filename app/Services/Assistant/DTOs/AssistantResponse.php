<?php

namespace App\Services\Assistant\DTOs;

use InvalidArgumentException;

final readonly class AssistantResponse
{
    private const TYPES = ['direct_answer', 'ambiguity', 'confirmation_required', 'error'];

    public function __construct(
        public string $type,
        public string $message,
        public array $data = [],
        public ?array $proposal = null,
    ) {
        if (! in_array($type, self::TYPES, true) || blank($message)) {
            throw new InvalidArgumentException('Invalid assistant response.');
        }

        if ($type === 'confirmation_required') {
            $actionType = $proposal['action_type'] ?? null;
            if (! in_array($actionType, ['create_appointment', 'create_work_order'], true)
                || ! is_string($proposal['token'] ?? null)
                || ! is_string($proposal['title'] ?? null)
                || ! is_array($proposal['display'] ?? null)
                || ($proposal['requires_confirmation'] ?? null) !== true) {
                throw new InvalidArgumentException('Invalid assistant proposal.');
            }
        }
    }

    public static function direct(string $message, array $data = []): self
    {
        return new self('direct_answer', $message, $data);
    }

    public static function ambiguity(string $message, array $options): self
    {
        return new self('ambiguity', $message, ['options' => array_values($options)]);
    }

    public static function confirmation(string $message, array $proposal): self
    {
        return new self('confirmation_required', $message, [], $proposal);
    }

    public static function error(string $message): self
    {
        return new self('error', $message);
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'message' => $this->message,
            'data' => $this->data,
            'proposal' => $this->proposal,
        ], fn ($value) => $value !== null);
    }
}
