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
        
        // Configure fields to collect
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
 * Example: Simple Usage with Static Methods (Legacy)
 */
class SimpleOnboardingService
{
    public function startOnboarding(): array
    {
        // Use legacy static methods
        OnboardingAgent::setFields(['name', 'email', 'company']);
        $result = OnboardingAgent::startSession();
        
        return $result;
    }
    
    public function chat(string $message, ?string $sessionId = null): ChatMessage
    {
        return OnboardingAgent::chat($message, $sessionId);
    }
    
    public function finish(?string $sessionId = null): array
    {
        return OnboardingAgent::finish($sessionId);
    }
}

/**
 * Example: Using the Facade
 */
class FacadeOnboardingService
{
    public function startOnboarding(): array
    {
        // Use facade with factory method
        $agent = OnboardingAgent::withFields(['name', 'email', 'company'], 'openai');
        $result = $agent->beginConversation();
        
        return [
            'session_id' => $result->sessionId,
            'first_message' => $result->firstMessage
        ];
    }
    
    public function chat(string $message, ?string $sessionId = null): ChatMessage
    {
        // Use facade singleton
        OnboardingAgent::configureFields(['name', 'email', 'company']);
        return OnboardingAgent::chat($message, $sessionId);
    }
}