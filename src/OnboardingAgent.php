<?php

namespace ElliotPutt\LaravelAiOnboarding;

use Illuminate\Support\Str;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingSessionResult;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingProgress;
use ElliotPutt\LaravelAiOnboarding\Traits\SessionManager;
use ElliotPutt\LaravelAiOnboarding\Traits\AIInteraction;
use ElliotPutt\LaravelAiOnboarding\Interfaces\OnboardingAgentCoreInterface;

class OnboardingAgent implements OnboardingAgentCoreInterface
{
    use SessionManager, AIInteraction;

    protected array $fields = [];
    protected ?string $sessionId = null;

    /**
     * Create a new OnboardingAgent instance
     */
    public function __construct(?string $aiModel = null)
    {
        if ($aiModel) {
            $this->setModel($aiModel);
        }
    }

    /**
     * Configure the fields to collect during onboarding
     */
    public function configureFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Start a new onboarding conversation
     */
    public function beginConversation(?string $sessionId = null): OnboardingSessionResult
    {
        if (empty($this->fields)) {
            throw new \Exception('No fields configured. Please call configureFields() first.');
        }

        // Generate UUID if no sessionId provided
        if (!$sessionId) {
            $sessionId = (string) Str::uuid();
        }

        $this->sessionId = $sessionId;

        // Store fields and set up session
        $this->setSessionFields($this->fields, $sessionId);
        $this->setCurrentSessionId($sessionId);
        $this->initializeSessionData($sessionId);

        // Get AI instructions and start the conversation
        $aiInstructions = $this->getAIInstructions($this->fields);
        $firstMessage = $this->getAIResponse($aiInstructions, 'Please ask the first question. be friendly and engaging.');

        // Set up first field
        $firstField = $this->fields[0] ?? null;
        if ($firstField) {
            $this->setCurrentField($sessionId, $firstField);
            $this->setLastQuestionIndex($sessionId, 0);
        }

        return OnboardingSessionResult::make(true, $sessionId, $firstMessage);
    }

    /**
     * Send a message and get AI response
     */
    public function chat(string $userMessage, ?string $sessionId = null): ChatMessage
    {
        $sessionId = $sessionId ?? $this->sessionId ?? $this->ensureSessionExists($sessionId);

        // Store the user's answer for the current field
        $currentField = $this->getCurrentField($sessionId);
        if ($currentField) {
            $this->storeExtractedField($sessionId, $currentField, $userMessage);
        }

        // Add user message to conversation history
        $this->addToConversation($sessionId, 'user', $userMessage);

        // Determine next question or completion
        $aiMessage = $this->generateNextResponse($sessionId, $userMessage, $currentField);

        // Add AI response to conversation history
        $this->addToConversation($sessionId, 'assistant', $aiMessage);

        return new ChatMessage('assistant', $aiMessage, $sessionId);
    }

    /**
     * Complete the onboarding and retrieve all collected data
     */
    public function completeOnboarding(?string $sessionId = null): array
    {
        $sessionId = $sessionId ?? $this->sessionId ?? $this->ensureSessionExists($sessionId);

        return $this->getExtractedFields($sessionId);
    }

    /**
     * Get the conversation history for a session
     */
    public function getConversationHistory(?string $sessionId = null): array
    {
        $sessionId = $sessionId ?? $this->sessionId ?? $this->ensureSessionExists($sessionId);

        return $this->getConversation($sessionId);
    }

    /**
     * Check if onboarding is complete
     */
    public function isComplete(?string $sessionId = null): bool
    {
        $sessionId = $sessionId ?? $this->sessionId ?? $this->ensureSessionExists($sessionId);

        $fields = $this->getSessionFields($sessionId);
        $currentIndex = $this->getLastQuestionIndex($sessionId);

        return $currentIndex >= count($fields) - 1;
    }

    /**
     * Get the current field being asked
     */
    public function getCurrentFieldName(?string $sessionId = null): ?string
    {
        $sessionId = $sessionId ?? $this->sessionId ?? $this->ensureSessionExists($sessionId);

        return $this->getCurrentField($sessionId);
    }

    /**
     * Get progress information
     */
    public function getProgress(?string $sessionId = null): OnboardingProgress
    {
        $sessionId = $sessionId ?? $this->sessionId ?? $this->ensureSessionExists($sessionId);

        $fields = $this->getSessionFields($sessionId);
        $currentIndex = $this->getLastQuestionIndex($sessionId);
        $totalFields = count($fields);

        return OnboardingProgress::make(
            $this->getCurrentField($sessionId),
            $currentIndex,
            $totalFields,
            $totalFields > 0 ? round(($currentIndex / $totalFields) * 100, 2) : 0,
            $this->isComplete($sessionId)
        );
    }

    /**
     * Generate the next AI response based on current progress
     */
    private function generateNextResponse(string $sessionId, string $userMessage, ?string $currentField): string
    {
        $fields = $this->getSessionFields($sessionId);
        $currentQuestionIndex = $this->getLastQuestionIndex($sessionId);
        $nextQuestionIndex = $currentQuestionIndex + 1;

        if ($nextQuestionIndex < count($fields)) {
            // There are more fields to ask
            $nextField = $fields[$nextQuestionIndex];
            $this->setCurrentField($sessionId, $nextField);
            $this->setLastQuestionIndex($sessionId, $nextQuestionIndex);

            // Get AI response for next question
            $aiInstructions = $this->getAIInstructions($fields);
            return $this->getAIResponse(
                $aiInstructions,
                "The user answered: '{$userMessage}' for the {$currentField} field. Now ask for the next field: {$nextField}. Be friendly and engaging."
            );
        } else {
            // All fields completed
            return "Thank you! I have all the information I need. Your onboarding is complete!";
        }
    }
}
