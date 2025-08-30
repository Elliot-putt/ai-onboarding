# Laravel AI Onboarding Package

A powerful Laravel package that provides AI-powered onboarding flows with automatic field extraction. This is a **pure library package** - no web routes, no views, just the core functionality that developers can integrate into their own applications.

## Features

- 🤖 **Multi-AI Model Support**: Works with OpenAI, Anthropic, and Ollama through Prism
- 💬 **Conversational Interface**: Natural chat-based onboarding experience
- 🔍 **Automatic Field Extraction**: AI automatically extracts specified fields from conversations
- 🚀 **Easy Integration**: Simple static methods for quick implementation
- 💾 **No Database Required**: Everything works in memory with session management
- ⚙️ **Highly Configurable**: Customizable AI models, prompts, and behavior
- 🎯 **Type Safe**: Full DTO support with type hints and validation
- 🔧 **Pure Library**: No web routes or views - integrate into your own controllers

## Installation

### 1. Install the Package

```bash
composer require elliotputt/laravel-ai-onboarding
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=ai-onboarding-config
```

### 3. Set Environment Variables

Add your AI API keys to your `.env` file:

```env
# AI Model Configuration
AI_ONBOARDING_DEFAULT_MODEL=openai

# OpenAI
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4

# Anthropic (Claude)
ANTHROPIC_API_KEY=your_anthropic_api_key_here
ANTHROPIC_MODEL=claude-3-sonnet-20240229

# Ollama (Local)
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama2
```

## Quick Start

### Basic Usage

```php
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

// Start a new onboarding session
$sessionId = OnboardingAgent::startSession();

// Set the fields you want extracted
OnboardingAgent::setFields([
    'name' => 'Customer name',
    'email' => 'Email address',
    'company' => 'Company name'
]);

// Chat with the user (this would typically happen in your controller)
$response = OnboardingAgent::chat("Hi, I'm John from TechCorp", $sessionId);

// When onboarding is complete, extract all fields
$results = OnboardingAgent::finish($sessionId);

// Access the extracted data
$extractedFields = $results->extractedFields;
$conversationSummary = $results->conversationSummary;
```

### Using DTOs for Type Safety

```php
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;
use ElliotPutt\LaravelAiOnboarding\Collections\OnboardingFieldCollection;

// Create typed fields
$fields = OnboardingFieldCollection::make([
    OnboardingField::make('customer_name', 'Full name of the customer', null, true),
    OnboardingField::make('company_name', 'Name of the company', null, true),
    OnboardingField::make('budget', 'Budget amount', '0', false, 'decimal')
]);

OnboardingAgent::setFields($fields);
```

### Using the Facade

```php
use ElliotPutt\LaravelAiOnboarding\Facades\OnboardingAgent;

$sessionId = OnboardingAgent::startSession();
OnboardingAgent::setFields(['name', 'email', 'company']);
// ... chat logic ...
$results = OnboardingAgent::finish($sessionId);
```

## Core Methods

### Session Management

```php
// Start a new session
$sessionId = OnboardingAgent::startSession();

// Start with custom session ID
$sessionId = OnboardingAgent::startSession('user-123');

// Set current session
OnboardingAgent::setCurrentSession($sessionId);

// Get current session ID
$currentSession = OnboardingAgent::getCurrentSessionId();

// Clear session data
OnboardingAgent::clearSession($sessionId);
```

### Field Configuration

```php
// Simple array format
OnboardingAgent::setFields([
    'name' => 'Customer name',
    'email' => 'Email address'
]);

// Using DTOs for advanced configuration
$fields = OnboardingFieldCollection::make([
    OnboardingField::make('name', 'Customer name', null, true, 'string'),
    OnboardingField::make('age', 'Customer age', null, false, 'integer'),
    OnboardingField::make('budget', 'Budget amount', '0', false, 'decimal')
]);

OnboardingAgent::setFields($fields);
```

### Chat and Message Handling

```php
// Send message and get AI response
$response = OnboardingAgent::chat("Hi, I'm John from TechCorp", $sessionId);
echo $response->content; // AI response content

// Manually add user message without AI response
OnboardingAgent::addUserMessage("I work at a startup", $sessionId);

// Manually add AI response
OnboardingAgent::addAssistantMessage("That's great! Tell me more about your startup.", $sessionId);
```

### Field Extraction and Results

```php
// Complete onboarding and extract fields
$results = OnboardingAgent::finish($sessionId);

// Access results through DTO
$extractedFields = $results->extractedFields;
$conversationSummary = $results->conversationSummary;
$totalMessages = $results->totalMessages;
$conversationHistory = $results->conversationHistory;

// Get specific field values
$customerName = OnboardingAgent::getField('name', $sessionId);
$hasEmail = OnboardingAgent::hasField('email', $sessionId);

// Get all extracted fields
$allFields = OnboardingAgent::getExtractedFields($sessionId);
```

## Advanced Usage

### Custom Field Types

```php
$fields = OnboardingFieldCollection::make([
    OnboardingField::make('name', 'Full name', null, true, 'string'),
    OnboardingField::make('age', 'Age', null, false, 'integer'),
    OnboardingField::make('budget', 'Budget', '0', false, 'decimal'),
    OnboardingField::make('is_decision_maker', 'Decision maker?', 'false', false, 'boolean'),
    OnboardingField::make('preferences', 'Product preferences', '[]', false, 'array')
]);
```

### Manual Conversation Control

```php
// Start session
$sessionId = OnboardingAgent::startSession();
OnboardingAgent::setFields(['name', 'email', 'company']);

// Add user message
OnboardingAgent::addUserMessage("Hi, I'm John Smith");

// Add AI response manually
OnboardingAgent::addAssistantMessage("Hello John! Nice to meet you. What company do you work for?");

// Add another user message
OnboardingAgent::addUserMessage("I work at TechCorp as a developer");

// Complete and extract
$results = OnboardingAgent::finish($sessionId);
```

### Multiple Sessions

```php
// Session 1
$session1 = OnboardingAgent::startSession('user-123');
OnboardingAgent::setFields(['name', 'email']);
OnboardingAgent::chat("Hi, I'm Alice", $session1);

// Switch to session 2
$session2 = OnboardingAgent::startSession('user-456');
OnboardingAgent::setFields(['company', 'role']);
OnboardingAgent::chat("Hi, I'm Bob from Acme Corp", $session2);

// Work with both sessions
$results1 = OnboardingAgent::finish($session1);
$results2 = OnboardingAgent::finish($session2);
```

## Integration Examples

### Customer Onboarding Service

```php
<?php

namespace App\Services;

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;
use ElliotPutt\LaravelAiOnboarding\Collections\OnboardingFieldCollection;

class CustomerOnboardingService
{
    public function startOnboarding(): string
    {
        $sessionId = OnboardingAgent::startSession();
        
        $fields = OnboardingFieldCollection::make([
            OnboardingField::make('customer_name', 'Full name of the customer', null, true),
            OnboardingField::make('company_name', 'Company name', null, true),
            OnboardingField::make('primary_goal', 'Main goal they want to achieve', null, true),
            OnboardingField::make('budget_range', 'Budget range', null, false),
            OnboardingField::make('timeline', 'Implementation timeline', null, false)
        ]);
        
        OnboardingAgent::setFields($fields);
        
        return $sessionId;
    }
    
    public function processMessage(string $message, string $sessionId): string
    {
        $response = OnboardingAgent::chat($message, $sessionId);
        return $response->content;
    }
    
    public function completeOnboarding(string $sessionId): array
    {
        $result = OnboardingAgent::finish($sessionId);
        return $result->extractedFields;
    }
}
```

### Controller Integration

```php
<?php

namespace App\Http\Controllers;

use App\Services\CustomerOnboardingService;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        protected CustomerOnboardingService $onboardingService
    ) {}
    
    public function start()
    {
        $sessionId = $this->onboardingService->startOnboarding();
        
        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Onboarding started successfully'
        ]);
    }
    
    public function chat(Request $request)
    {
        $response = $this->onboardingService->processMessage(
            $request->input('message'),
            $request->input('session_id')
        );
        
        return response()->json(['response' => $response]);
    }
    
    public function complete(Request $request)
    {
        $extractedData = $this->onboardingService->completeOnboarding(
            $request->input('session_id')
        );
        
        // Process the extracted data
        // Save to database, send emails, etc.
        
        return response()->json([
            'success' => true,
            'data' => $extractedData
        ]);
    }
}
```

## Configuration

### AI Models

```php
'models' => [
    'openai' => [
        'driver' => 'openai',
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
    ],
    'anthropic' => [
        'driver' => 'anthropic',
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
    ],
    'ollama' => [
        'driver' => 'ollama',
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama2'),
    ],
],
```

### Chat Settings

```php
'chat' => [
    'max_messages' => 50,
    'session_timeout' => 3600, // 1 hour
],
```

## DTOs and Type Safety

### OnboardingField

```php
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;

$field = OnboardingField::make(
    key: 'customer_name',
    description: 'Full name of the customer',
    defaultValue: null,
    required: true,
    type: 'string'
);

// Access properties
echo $field->key;           // 'customer_name'
echo $field->description;    // 'Full name of the customer'
echo $field->required;       // true
echo $field->type;           // 'string'
```

### ChatMessage

```php
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;

$message = ChatMessage::make('user', 'Hello, I need help', 'session-123');

// Access properties
echo $message->role;         // 'user'
echo $message->content;      // 'Hello, I need help'
echo $message->timestamp;    // ISO timestamp
echo $message->sessionId;    // 'session-123'

// Helper methods
$message->isUser();          // true
$message->isAssistant();     // false
```

### OnboardingResult

```php
$result = OnboardingAgent::finish($sessionId);

// Access properties
$sessionId = $result->sessionId;
$extractedFields = $result->extractedFields;
$conversationSummary = $result->conversationSummary;
$totalMessages = $result->totalMessages;
$conversationHistory = $result->conversationHistory;

// Helper methods
$customerName = $result->getField('customer_name');
$hasEmail = $result->hasField('email');
$allFields = $result->getFields();
```

## Troubleshooting

### Common Issues

1. **"Class not found" errors**
   - Run `composer dump-autoload`
   - Ensure the package is properly installed

2. **API key errors**
   - Verify your API keys are correct in `.env`
   - Check that the AI provider is accessible

3. **Field extraction fails**
   - Verify that the AI model can generate valid JSON responses
   - Check that field keys are properly defined

### Debug Mode

Enable debug mode to see detailed error messages:

```php
// In your controller
try {
    $results = OnboardingAgent::finish($sessionId);
} catch (\Exception $e) {
    \Log::error('Onboarding error: ' . $e->getMessage());
    return response()->json(['error' => $e->getMessage()], 500);
}
```

## Testing

Run the package tests:

```bash
composer test
```

Or run specific test files:

```bash
./vendor/bin/phpunit tests/OnboardingAgentTest.php
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

If you encounter any issues or have questions, please open an issue on GitHub or contact the maintainers. 