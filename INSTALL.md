# Installation Guide - Library Version

## Prerequisites

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- Composer
- Valid API key for at least one AI provider (OpenAI, Anthropic, or Ollama)

## Step 1: Install the Package

```bash
composer require elliotputt/laravel-ai-onboarding
```

## Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=ai-onboarding-config
```

This will create `config/ai-onboarding.php` in your Laravel application.

## Step 3: Configure Environment Variables

Add the following to your `.env` file:

```env
# AI Model Configuration
AI_ONBOARDING_DEFAULT_MODEL=openai

# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_MODEL=gpt-4

# Anthropic Configuration (Optional)
ANTHROPIC_API_KEY=your_anthropic_api_key_here
ANTHROPIC_MODEL=claude-3-sonnet-20240229

# Ollama Configuration (Optional - for local models)
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama2
```

## Step 4: Test the Installation

### Test Programmatically

Create a test route in `routes/web.php`:

```php
Route::get('/test-onboarding', function () {
    use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
    
    try {
        // Start session
        $sessionId = OnboardingAgent::startSession();
        
        // Set fields
        OnboardingAgent::setFields([
            'name' => 'Customer name',
            'email' => 'Email address',
            'company' => 'Company name'
        ]);
        
        // Test chat
        $response = OnboardingAgent::chat("Hi, I'm John from TechCorp", $sessionId);
        
        // Finish and extract
        $results = OnboardingAgent::finish($sessionId);
        
        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'chat_response' => $response->content,
            'extracted_fields' => $results->extractedFields
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
```

Visit `/test-onboarding` to test the package functionality.

## Step 5: Integration Examples

### Basic Controller Integration

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

class OnboardingController extends Controller
{
    public function start()
    {
        $sessionId = OnboardingAgent::startSession();
        
        OnboardingAgent::setFields([
            'customer_name' => 'Customer name',
            'company' => 'Company name',
            'needs' => 'What they need'
        ]);
        
        return response()->json(['session_id' => $sessionId]);
    }
    
    public function chat(Request $request)
    {
        $response = OnboardingAgent::chat(
            $request->input('message'),
            $request->input('session_id')
        );
        
        return response()->json(['response' => $response->content]);
    }
    
    public function complete(Request $request)
    {
        $results = OnboardingAgent::finish($request->input('session_id'));
        
        // Process the extracted data
        $customerData = $results->extractedFields;
        
        // Save to database, send emails, etc.
        
        return response()->json(['success' => true, 'data' => $customerData]);
    }
}
```

### Using DTOs for Type Safety

```php
<?php

namespace App\Services;

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;
use ElliotPutt\LaravelAiOnboarding\Collections\OnboardingFieldCollection;

class OnboardingService
{
    public function startOnboarding(): string
    {
        $sessionId = OnboardingAgent::startSession();
        
        $fields = OnboardingFieldCollection::make([
            OnboardingField::make('customer_name', 'Full name of the customer', null, true),
            OnboardingField::make('company_name', 'Company name', null, true),
            OnboardingField::make('budget', 'Budget amount', '0', false, 'decimal'),
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

### Using the Facade

```php
use ElliotPutt\LaravelAiOnboarding\Facades\OnboardingAgent;

$sessionId = OnboardingAgent::startSession();
OnboardingAgent::setFields(['name', 'email', 'company']);
$results = OnboardingAgent::finish($sessionId);
```

## Step 6: Advanced Usage

### Multiple Sessions

```php
// Start multiple sessions
$session1 = OnboardingAgent::startSession('user-123');
$session2 = OnboardingAgent::startSession('user-456');

// Work with session 1
OnboardingAgent::setCurrentSession($session1);
OnboardingAgent::setFields(['name', 'email']);
OnboardingAgent::chat("Hi, I'm Alice", $session1);

// Switch to session 2
OnboardingAgent::setCurrentSession($session2);
OnboardingAgent::setFields(['company', 'role']);
OnboardingAgent::chat("Hi, I'm Bob from Acme Corp", $session2);

// Complete both sessions
$results1 = OnboardingAgent::finish($session1);
$results2 = OnboardingAgent::finish($session2);
```

### Manual Conversation Control

```php
$sessionId = OnboardingAgent::startSession();
OnboardingAgent::setFields(['name', 'email', 'company']);

// Add user message without AI response
OnboardingAgent::addUserMessage("Hi, I'm John Smith");

// Add AI response manually
OnboardingAgent::addAssistantMessage("Hello John! Nice to meet you. What company do you work for?");

// Add another user message
OnboardingAgent::addUserMessage("I work at TechCorp as a developer");

// Complete and extract
$results = OnboardingAgent::finish($sessionId);
```

### Field Management

```php
// Get specific field values
$customerName = OnboardingAgent::getField('name', $sessionId);
$hasEmail = OnboardingAgent::hasField('email', $sessionId);

// Get all extracted fields
$allFields = OnboardingAgent::getExtractedFields($sessionId);

// Get conversation history
$history = OnboardingAgent::getHistory($sessionId);
```

## Step 7: Customization

### Custom AI Behavior

Extend the OnboardingAgent class:

```php
<?php

namespace App\Services;

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

class CustomOnboardingAgent extends OnboardingAgent
{
    protected static function buildConversationContext(string $sessionId): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a friendly customer success specialist. Be warm and professional.'
            ]
        ];
        
        // Add conversation history
        foreach (static::$conversationHistory[$sessionId] as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content
            ];
        }
        
        return $messages;
    }
}
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

Enable debug mode in your `.env`:

```env
APP_DEBUG=true
```

Check the Laravel logs at `storage/logs/laravel.log` for detailed error information.

## Next Steps

- Review the examples in the `examples/` directory
- Check the README.md for advanced usage patterns
- Integrate with your existing authentication system
- Add database persistence if needed
- Build your own chat interface or API endpoints

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review the package documentation
3. Check Laravel and AI provider logs
4. Open an issue on the package repository

## Testing

Run the package tests:

```bash
composer test
```

Or run specific test files:

```bash
./vendor/bin/phpunit tests/OnboardingAgentTest.php
``` 