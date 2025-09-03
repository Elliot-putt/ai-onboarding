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

        // Add user message to conversation history
        $this->addToConversation($sessionId, 'user', $userMessage);

        // Get current field and validate the user's answer
        $currentField = $this->getCurrentField($sessionId);
        $isValidAnswer = true;
        
        if ($currentField) {
            // Create a question from the field name for validation
            $question = $this->createQuestionFromField($currentField);
            
            // Validate the user's answer
            $isValidAnswer = $this->validateUserAnswer($question, $userMessage);
            
            // Only store the field if the answer is valid
            if ($isValidAnswer) {
                $this->storeExtractedField($sessionId, $currentField, $userMessage);
            }
        }

        // Determine next question or completion
        $aiMessage = $this->generateNextResponse($sessionId, $userMessage, $currentField, $isValidAnswer);

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
    private function generateNextResponse(string $sessionId, string $userMessage, ?string $currentField, bool $isValidAnswer = true): string
    {
        $fields = $this->getSessionFields($sessionId);
        $currentQuestionIndex = $this->getLastQuestionIndex($sessionId);
        $nextQuestionIndex = $currentQuestionIndex + 1;

        if (!$isValidAnswer) {
            // User's answer was not valid, ask the same question again
            $aiInstructions = $this->getAIInstructions($fields);
            return $this->getAIResponse(
                $aiInstructions,
                "The user's response '{$userMessage}' was not a valid answer for the {$currentField} field. Please ask the question again in a different way, but make sure to ask for the {$currentField} field at the end of your response. Be friendly and encouraging."
            );
        }

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

    /**
     * Create a question from a field name for validation purposes
     */
    private function createQuestionFromField(string $field): string
    {
        // Convert field name to a question format
        $question = "What is your " . strtolower($field) . "?";
        
        // Handle common field types with better questions
        $fieldLower = strtolower($field);
        if (str_contains($fieldLower, 'name')) {
            $question = "What is your name?";
        } elseif (str_contains($fieldLower, 'email')) {
            $question = "What is your email address?";
        } elseif (str_contains($fieldLower, 'phone')) {
            $question = "What is your phone number?";
        } elseif (str_contains($fieldLower, 'age')) {
            $question = "What is your age?";
        } elseif (str_contains($fieldLower, 'address')) {
            $question = "What is your address?";
        }
        
        return $question;
    }
}
