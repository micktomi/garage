<?php

namespace App\Services\Assistant\DTOs;

final readonly class ToolResult
{
    public function __construct(
        public string $status,
        public string $message,
        public array $data = [],
        public ?AssistantResponse $terminalResponse = null,
    ) {}

    public static function success(string $message, array $data): self
    {
        return new self('success', $message, $data);
    }

    public static function terminal(AssistantResponse $response): self
    {
        return new self('terminal', $response->message, terminalResponse: $response);
    }

    public function forGemini(): array
    {
        return ['status' => $this->status, 'message' => $this->message, 'data' => $this->data];
    }
}
