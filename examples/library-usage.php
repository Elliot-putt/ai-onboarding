<?php

/**
 * Example: Library Usage of Laravel AI Onboarding Package
 * 
 * This example shows how to use the package as a pure library
 * without web routes - just the core functionality.
 */

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;
use ElliotPutt\LaravelAiOnboarding\Collections\OnboardingFieldCollection;

class CustomerOnboardingService
{
    /**
     * Start a new customer onboarding session
     */
    public function startOnboarding(): string
    {
        // Start a new session
        $sessionId = OnboardingAgent::startSession();
        
        // Define the fields we want to extract
        $fields = OnboardingFieldCollection::make([
            OnboardingField::make('customer_name', 'Full name of the customer', null, true),
            OnboardingField::make('company_name', 'Name of the company they work for', null, true),
            OnboardingField::make('job_title', 'Their current job title or role'),
            OnboardingField::make('team_size', 'Size of their team or organization'),
            OnboardingField::make('primary_goal', 'Main goal they want to achieve', null, true),
            OnboardingField::make('budget_range', 'Their budget range for the solution'),
            OnboardingField::make('timeline', 'When they need this implemented by'),
            OnboardingField::make('contact_email', 'Their email address for follow-up', null, true, 'email')
        ]);
        
        // Set the fields for extraction
        OnboardingAgent::setFields($fields);
        
        return $sessionId;
    }
    
    /**
     * Process a customer message and get AI response
     */
    public function processMessage(string $message, string $sessionId): string
    {
        // Send message to AI and get response
        $response = OnboardingAgent::chat($message, $sessionId);
        
        return $response->content;
    }
    
    /**
     * Manually add a user message without AI response
     */
    public function addUserMessage(string $message, string $sessionId): void
    {
        OnboardingAgent::addUserMessage($message, $sessionId);
    }
    
    /**
     * Manually add an AI response
     */
    public function addAIResponse(string $message, string $sessionId): void
    {
        OnboardingAgent::addAssistantMessage($message, $sessionId);
    }
    
    /**
     * Complete the onboarding and get extracted data
     */
    public function completeOnboarding(string $sessionId): array
    {
        // Finish the onboarding and extract all fields
        $result = OnboardingAgent::finish($sessionId);
        
        // Return the extracted data
        return [
            'session_id' => $result->sessionId,
            'extracted_fields' => $result->extractedFields,
            'conversation_summary' => $result->conversationSummary,
            'total_messages' => $result->totalMessages,
            'conversation_history' => $result->conversationHistory
        ];
    }
    
    /**
     * Get specific field value
     */
    public function getFieldValue(string $fieldKey, string $sessionId): mixed
    {
        return OnboardingAgent::getField($fieldKey, $sessionId);
    }
    
    /**
     * Check if a field has been extracted
     */
    public function hasFieldValue(string $fieldKey, string $sessionId): bool
    {
        return OnboardingAgent::hasField($fieldKey, $sessionId);
    }
}

/**
 * Example: Simple Usage with Array Fields
 */
class SimpleOnboardingService
{
    public function startOnboarding(): string
    {
        $sessionId = OnboardingAgent::startSession();
        
        // Simple array format for fields
        OnboardingAgent::setFields([
            'name' => 'Customer name',
            'email' => 'Email address',
            'company' => 'Company name'
        ]);
        
        return $sessionId;
    }
    
    public function chat(string $message, string $sessionId): string
    {
        $response = OnboardingAgent::chat($message, $sessionId);
        return $response->content;
    }
    
    public function finish(string $sessionId): array
    {
        $result = OnboardingAgent::finish($sessionId);
        return $result->extractedFields;
    }
} 