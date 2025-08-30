<?php

namespace ElliotPutt\LaravelAiOnboarding\DTOs;

class OnboardingResult
{
    public function __construct(
        public readonly string $sessionId,
        public readonly array $extractedFields,
        public readonly array $conversationSummary,
        public readonly int $totalMessages,
        public readonly array $conversationHistory
    ) {}

    public static function make(string $sessionId, array $extractedFields, array $conversationSummary, int $totalMessages, array $conversationHistory): self
    {
        return new self($sessionId, $extractedFields, $conversationSummary, $totalMessages, $conversationHistory);
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'extracted_fields' => $this->extractedFields,
            'conversation_summary' => $this->conversationSummary,
            'total_messages' => $this->totalMessages,
            'conversation_history' => $this->conversationHistory,
        ];
    }

    public function getField(string $key): mixed
    {
        return $this->extractedFields[$key] ?? null;
    }

    public function hasField(string $key): bool
    {
        return isset($this->extractedFields[$key]);
    }

    public function getFields(): array
    {
        return $this->extractedFields;
    }
} 