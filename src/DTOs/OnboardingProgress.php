<?php

namespace ElliotPutt\LaravelAiOnboarding\DTOs;

class OnboardingProgress
{
    public function __construct(
        public readonly ?string $currentField,
        public readonly int $currentIndex,
        public readonly int $totalFields,
        public readonly float $progressPercentage,
        public readonly bool $isComplete
    ) {}

    public static function make(
        ?string $currentField,
        int $currentIndex,
        int $totalFields,
        float $progressPercentage,
        bool $isComplete
    ): self {
        return new self($currentField, $currentIndex, $totalFields, $progressPercentage, $isComplete);
    }

    public function toArray(): array
    {
        return [
            'current_field' => $this->currentField,
            'current_index' => $this->currentIndex,
            'total_fields' => $this->totalFields,
            'progress_percentage' => $this->progressPercentage,
            'is_complete' => $this->isComplete,
        ];
    }

    public function getRemainingFields(): int
    {
        return $this->totalFields - $this->currentIndex;
    }

    public function isFirstField(): bool
    {
        return $this->currentIndex === 0;
    }

    public function isLastField(): bool
    {
        return $this->currentIndex === $this->totalFields - 1;
    }
}
