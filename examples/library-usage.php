<?php

/**
 * Example: Library Usage of Laravel AI Onboarding Package
 *
 * This example shows how to use the package as a pure library
 * without web routes - just the core functionality.
 */

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;

class CustomerOnboardingService
{
    private OnboardingAgent $agent;

    public function __construct()
    {
        // Create agent with specific AI model
        $this->agent = new OnboardingAgent('anthropic');

        // Configure fields to collect (simple format - backward compatible)
        $this->agent->configureFields([
            'customer_name',
            'company_name',
            'job_title',
            'team_size',
            'primary_goal',
            'budget_range',
            'timeline',
            'contact_email'
        ]);
    }

    /**
     * Start a new customer onboarding session
     */
    public function startOnboarding(): array
    {
        // Start a new session
        $result = $this->agent->beginConversation();

        return [
            'session_id' => $result->sessionId,
            'first_message' => $result->firstMessage,
            'success' => $result->success
        ];
    }

    /**
     * Process a customer message and get AI response
     */
    public function processMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        // Send message to AI and get response
        return $this->agent->chat($message, $sessionId);
    }

    /**
     * Get onboarding progress
     */
    public function getProgress(?string $sessionId = null): array
    {
        $progress = $this->agent->getProgress($sessionId);

        return [
            'current_field' => $progress->currentField,
            'progress_percentage' => $progress->progressPercentage,
            'is_complete' => $progress->isComplete,
            'remaining_fields' => $progress->getRemainingFields()
        ];
    }

    /**
     * Complete the onboarding and get extracted data
     */
    public function completeOnboarding(?string $sessionId = null): array
    {
        // Finish the onboarding and extract all fields
        return $this->agent->completeOnboarding($sessionId);
    }

    /**
     * Get conversation history
     */
    public function getConversationHistory(?string $sessionId = null): array
    {
        return $this->agent->getConversationHistory($sessionId);
    }
}

/**
 * Example: Enhanced Customer Onboarding with Laravel Validation
 * 
 * This shows the new validation feature in action
 */
class EnhancedCustomerOnboardingService
{
    private OnboardingAgent $agent;

    public function __construct()
    {
        // Create agent with specific AI model
        $this->agent = new OnboardingAgent('anthropic');

        // Configure fields with Laravel validation rules using the new clean syntax
        $this->agent->configureFields([
            'fields' => [
                'customer_name',
                'contact_email',
                'company_name',
                'team_size',
                'budget_range',
                'timeline'
            ],
            'rules' => [
                'contact_email' => ['required', 'email'],
                'company_name' => ['required', 'string', 'min:2', 'max:100'],
                'team_size' => ['required', 'in:1-10,11-50,51-200,201-1000,1000+'],
                'budget_range' => ['required', 'numeric', 'min:1000', 'max:100000'],
                'timeline' => ['nullable', 'string', 'max:255']
            ]
        ]);
    }

    /**
     * Start a new customer onboarding session with validation
     */
    public function startOnboarding(): array
    {
        $result = $this->agent->beginConversation();

        return [
            'session_id' => $result->sessionId,
            'first_message' => $result->firstMessage,
            'success' => $result->success
        ];
    }

    /**
     * Process a customer message with dual validation (AI + Laravel)
     */
    public function processMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        // This will automatically validate against both AI and Laravel rules
        return $this->agent->chat($message, $sessionId);
    }

    /**
     * Complete the onboarding and get validated data
     */
    public function completeOnboarding(?string $sessionId = null): array
    {
        // All data will have passed both AI and Laravel validation
        return $this->agent->completeOnboarding($sessionId);
    }
}
