<?php

namespace ElliotPutt\LaravelAiOnboarding\Interfaces;

use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingSessionResult;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingProgress;

interface OnboardingAgentCoreInterface
{
    /**
     * Set the AI model to use for this instance
     */
    public function setModel(?string $model): self;

    /**
     * Get the AI model for this instance
     */
    public function getModel(): ?string;

    /**
     * Configure the fields to collect during onboarding
     */
    public function configureFields(array $fields): self;

    /**
     * Start a new onboarding conversation
     */
    public function beginConversation(?string $sessionId = null): OnboardingSessionResult;

    /**
     * Send a message and get AI response
     */
    public function chat(string $userMessage, ?string $sessionId = null): ChatMessage;

    /**
     * Complete the onboarding and retrieve all collected data
     */
    public function completeOnboarding(?string $sessionId = null): array;

    /**
     * Get the conversation history for a session
     */
    public function getConversationHistory(?string $sessionId = null): array;

    /**
     * Check if onboarding is complete
     */
    public function isComplete(?string $sessionId = null): bool;

    /**
     * Get the current field being asked
     */
    public function getCurrentFieldName(?string $sessionId = null): ?string;

    /**
     * Get progress information
     */
    public function getProgress(?string $sessionId = null): OnboardingProgress;
}
