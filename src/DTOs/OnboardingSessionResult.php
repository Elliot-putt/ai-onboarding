<?php

namespace ElliotPutt\LaravelAiOnboarding\DTOs;

class OnboardingSessionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $sessionId,
        public readonly string $firstMessage
    ) {}

    public static function make(bool $success, string $sessionId, string $firstMessage): self
    {
        return new self($success, $sessionId, $firstMessage);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'session_id' => $this->sessionId,
            'first_message' => $this->firstMessage,
        ];
    }
}
