<?php

namespace ElliotPutt\LaravelAiOnboarding\DTOs;

class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly ?string $aiValidationMessage = null,
        public readonly ?string $laravelValidationMessage = null
    ) {}

    public static function success(): self
    {
        return new self(true, [], null, null);
    }

    public static function failure(array $errors = [], ?string $aiMessage = null, ?string $laravelMessage = null): self
    {
        return new self(false, $errors, $aiMessage, $laravelMessage);
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'ai_validation_message' => $this->aiValidationMessage,
            'laravel_validation_message' => $this->laravelValidationMessage,
        ];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrorMessage(): ?string
    {
        if ($this->aiValidationMessage) {
            return $this->aiValidationMessage;
        }

        if ($this->laravelValidationMessage) {
            return $this->laravelValidationMessage;
        }

        if (!empty($this->errors)) {
            return implode(', ', $this->errors);
        }

        return null;
    }
}
