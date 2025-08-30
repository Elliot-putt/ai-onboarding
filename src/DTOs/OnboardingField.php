<?php

namespace ElliotPutt\LaravelAiOnboarding\DTOs;

class OnboardingField
{
    public function __construct(
        public readonly string $key,
        public readonly string $description,
        public readonly ?string $defaultValue = null,
        public readonly bool $required = false,
        public readonly string $type = 'string'
    ) {}

    public static function make(string $key, string $description, ?string $defaultValue = null, bool $required = false, string $type = 'string'): self
    {
        return new self($key, $description, $defaultValue, $required, $type);
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'description' => $this->description,
            'default_value' => $this->defaultValue,
            'required' => $this->required,
            'type' => $this->type,
        ];
    }
} 