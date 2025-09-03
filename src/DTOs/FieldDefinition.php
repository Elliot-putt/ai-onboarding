<?php

namespace ElliotPutt\LaravelAiOnboarding\DTOs;

class FieldDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly array $validationRules = [],
        public readonly ?string $label = null,
        public readonly ?string $description = null
    ) {}

    public static function make(string $name, array $validationRules = [], ?string $label = null, ?string $description = null): self
    {
        return new self($name, $validationRules, $label, $description);
    }

    public static function fromString(string $fieldName): self
    {
        return new self($fieldName, [], null, null);
    }

    public static function fromArray(array $fieldData): self
    {
        if (is_string($fieldData)) {
            return self::fromString($fieldData);
        }

        if (is_array($fieldData)) {
            $name = $fieldData['name'] ?? $fieldData[0] ?? '';
            $rules = $fieldData['rules'] ?? $fieldData['validation'] ?? $fieldData['validation_rules'] ?? [];
            $label = $fieldData['label'] ?? null;
            $description = $fieldData['description'] ?? null;

            return new self($name, $rules, $label, $description);
        }

        throw new \InvalidArgumentException('Invalid field data format');
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'validation_rules' => $this->validationRules,
            'label' => $this->label,
            'description' => $this->description,
        ];
    }

    public function hasValidationRules(): bool
    {
        return !empty($this->validationRules);
    }

    public function getDisplayName(): string
    {
        return $this->label ?? $this->name;
    }
}
