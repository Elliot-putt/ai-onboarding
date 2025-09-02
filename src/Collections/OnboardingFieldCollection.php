<?php

namespace ElliotPutt\LaravelAiOnboarding\Collections;

use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;
use Illuminate\Support\Collection;

class OnboardingFieldCollection extends Collection
{
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    public static function make($items = []): self
    {
        return new static($items);
    }

    public static function fromFields(array $fields): self
    {
        $collection = new self();
        
        foreach ($fields as $field) {
            if (is_string($field)) {
                $collection->push(OnboardingField::make($field, "Extract {$field}"));
            } elseif (is_array($field)) {
                $collection->push(OnboardingField::make(
                    $field['key'] ?? $field[0] ?? '',
                    $field['description'] ?? $field[1] ?? '',
                    $field['default_value'] ?? $field[2] ?? null,
                    $field['required'] ?? $field[3] ?? false,
                    $field['type'] ?? $field[4] ?? 'string'
                ));
            } elseif ($field instanceof OnboardingField) {
                $collection->push($field);
            }
        }
        
        return $collection;
    }

    public function getKeys(): array
    {
        return $this->pluck('key')->toArray();
    }

    public function getDescriptions(): array
    {
        return $this->pluck('description', 'key')->toArray();
    }

    public function getRequiredFields(): self
    {
        return $this->filter(fn($field) => $field->required);
    }

    public function getOptionalFields(): self
    {
        return $this->filter(fn($field) => !$field->required);
    }

    public function findByKey(string $key): ?OnboardingField
    {
        return $this->firstWhere('key', $key);
    }

    public function hasKey(string $key): bool
    {
        return $this->contains('key', $key);
    }

    public function toArray(): array
    {
        return $this->map(fn($field) => $field->toArray())->toArray();
    }
} 