<?php

namespace ElliotPutt\LaravelAiOnboarding\DTOs;

class ChatMessage
{
    public function __construct(
        public readonly string $role,
        public readonly string $content,
        public readonly string $timestamp,
        public readonly ?string $sessionId = null
    ) {}

    public static function make(string $role, string $content, ?string $sessionId = null): self
    {
        return new self($role, $content, date('c'), $sessionId);
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'timestamp' => $this->timestamp,
            'session_id' => $this->sessionId,
        ];
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
} 