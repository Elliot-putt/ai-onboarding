<?php

/**
 * Example: Using Laravel Validation with AI Onboarding Package
 *
 * This example demonstrates how to use the enhanced validation system
 * that combines AI validation with Laravel validation rules.
 */

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;

class CustomerOnboardingWithValidation
{
    private OnboardingAgent $agent;

    public function __construct()
    {
        // Create agent with specific AI model
        $this->agent = new OnboardingAgent('anthropic');

        // Configure fields with Laravel validation rules using the new clean syntax
        $this->agent->configureFields([
            'fields' => [
                'name',
                'email',
                'phone',
                'company_size',
                'website',
                'start_date',
                'budget'
            ],
            'rules' => [
                'email' => ['required', 'email'],
                'phone' => ['nullable', 'string', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
                'company_size' => ['required', 'in:1-10,11-50,51-200,201-1000,1000+'],
                'website' => ['nullable', 'url', 'max:255'],
                'start_date' => ['required', 'date', 'after:today'],
                'budget' => ['required', 'numeric', 'min:1000', 'max:100000']
            ]
        ]);
    }

    /**
     * Start a new customer onboarding session with validation
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
     * Process a customer message with dual validation
     */
    public function processMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        // Send message to AI and get response
        // This will automatically validate against both AI and Laravel rules
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
     * Complete the onboarding and get validated data
     */
    public function completeOnboarding(?string $sessionId = null): array
    {
        // Finish the onboarding and extract all fields
        // All data will have passed both AI and Laravel validation
        return $this->agent->completeOnboarding($sessionId);
    }
}

// Example usage demonstrating different validation scenarios
class ValidationExamples
{
    public function demonstrateValidation()
    {
        $onboarding = new CustomerOnboardingWithValidation();
        
        // Start the session
        $result = $onboarding->startOnboarding();
        $sessionId = $result['session_id'];
        
        echo "Session started: {$sessionId}\n";
        echo "First message: {$result['first_message']}\n\n";
        
        // Example conversation flow with validation
        $conversation = [
            "Hi, I'm John Smith", // Valid name
            "john@example.com", // Valid email
            "555-123-4567", // Valid phone (if regex passes)
            "51-200", // Valid company size
            "https://example.com", // Valid website
            "2024-02-15", // Valid future date
            "5000" // Valid budget
        ];
        
        foreach ($conversation as $message) {
            echo "User: {$message}\n";
            $response = $onboarding->processMessage($message, $sessionId);
            echo "AI: {$response->content}\n\n";
            
            // Check progress
            $progress = $onboarding->getProgress($sessionId);
            echo "Progress: {$progress['progress_percentage']}% complete\n";
            echo "Current field: {$progress['current_field']}\n\n";
        }
        
        // Complete onboarding
        $data = $onboarding->completeOnboarding($sessionId);
        echo "Final extracted data:\n";
        print_r($data);
    }
    
    public function demonstrateValidationErrors()
    {
        $onboarding = new CustomerOnboardingWithValidation();
        $result = $onboarding->startOnboarding();
        $sessionId = $result['session_id'];
        
        echo "=== Validation Error Examples ===\n\n";
        
        // Invalid email example
        echo "User: invalid-email\n";
        $response = $onboarding->processMessage("invalid-email", $sessionId);
        echo "AI: {$response->content}\n\n";
        
        // Invalid phone example
        echo "User: not-a-phone\n";
        $response = $onboarding->processMessage("not-a-phone", $sessionId);
        echo "AI: {$response->content}\n\n";
        
        // Invalid budget example
        echo "User: 500\n"; // Below minimum
        $response = $onboarding->processMessage("500", $sessionId);
        echo "AI: {$response->content}\n\n";
    }
}

// Usage examples
echo "=== Laravel AI Onboarding with Validation ===\n\n";

// Run the examples
$examples = new ValidationExamples();
$examples->demonstrateValidation();
$examples->demonstrateValidationErrors();
