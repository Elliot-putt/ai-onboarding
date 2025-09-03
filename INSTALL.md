# Installation Guide - Library Version

## Prerequisites

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- Composer
- Valid API key for at least one AI provider (OpenAI, Anthropic, Gemini, or Ollama)

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

# Gemini Configuration (Optional)
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-2.0-flash
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta

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
        // Create agent instance with fields
        $agent = OnboardingAgent::withFields([
            'customer_name',
            'email_address', 
            'company_name'
        ], 'openai');
        
        // Start conversation
        $result = $agent->beginConversation();
        
        // Test chat
        $response = $agent->chat("Hi, I'm John from TechCorp", $result->sessionId);
        
        // Complete and extract
        $extractedData = $agent->completeOnboarding($result->sessionId);
        
        return response()->json([
            'success' => true,
            'session_id' => $result->sessionId,
            'first_message' => $result->firstMessage,
            'chat_response' => $response->content,
            'extracted_fields' => $extractedData
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
    private OnboardingAgent $agent;
    
    public function __construct()
    {
        // Initialize agent with fields
        $this->agent = OnboardingAgent::withFields([
            'customer_name',
            'company_name',
            'primary_needs'
        ], 'anthropic');
    }
    
    public function start()
    {
        $result = $this->agent->beginConversation();
        
        return response()->json([
            'session_id' => $result->sessionId,
            'first_message' => $result->firstMessage
        ]);
    }
    
    public function chat(Request $request)
    {
        $response = $this->agent->chat(
            $request->input('message'),
            $request->input('session_id')
        );
        
        return response()->json(['response' => $response->content]);
    }
    
    public function complete(Request $request)
    {
        $extractedData = $this->agent->completeOnboarding($request->input('session_id'));
        
        // Process the extracted data
        // Save to database, send emails, etc.
        
        return response()->json(['success' => true, 'data' => $extractedData]);
    }
}
```

### Using DTOs for Type Safety

```php
<?php

namespace App\Services;

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingSessionResult;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingProgress;

class OnboardingService
{
    private OnboardingAgent $agent;

    public function __construct()
    {
        $this->agent = new OnboardingAgent('anthropic');
        $this->agent->configureFields([
            'customer_name',
            'company_name', 
            'budget',
            'timeline'
        ]);
    }
    
    public function startOnboarding(): OnboardingSessionResult
    {
        return $this->agent->beginConversation();
    }
    
    public function processMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        return $this->agent->chat($message, $sessionId);
    }
    
    public function getProgress(?string $sessionId = null): OnboardingProgress
    {
        return $this->agent->getProgress($sessionId);
    }
    
    public function completeOnboarding(?string $sessionId = null): array
    {
        return $this->agent->completeOnboarding($sessionId);
    }
}
```

### Using the Facade

```php
use ElliotPutt\LaravelAiOnboarding\Facades\OnboardingAgent;

// Modern facade usage - recommended approach
$agent = OnboardingAgent::withFields(['name', 'email', 'company'], 'anthropic');
$result = $agent->beginConversation();
$response = $agent->chat('John Doe', $result->sessionId);
$data = $agent->completeOnboarding($result->sessionId);

// Alternative: Create instance and configure separately
$agent = OnboardingAgent::create('openai');
$agent->configureFields(['name', 'email', 'company']);
$result = $agent->beginConversation();
```

## Step 6: Advanced Usage

### Multiple Sessions

```php
// Create separate agent instances for different users
$agent1 = OnboardingAgent::withFields(['name', 'email'], 'openai');
$agent2 = OnboardingAgent::withFields(['company', 'role'], 'anthropic');

// Start sessions for different users
$result1 = $agent1->beginConversation('user-123');
$result2 = $agent2->beginConversation('user-456');

// Work with session 1
$response1 = $agent1->chat("Hi, I'm Alice", $result1->sessionId);

// Work with session 2
$response2 = $agent2->chat("Hi, I'm Bob from Acme Corp", $result2->sessionId);

// Complete both sessions
$data1 = $agent1->completeOnboarding($result1->sessionId);
$data2 = $agent2->completeOnboarding($result2->sessionId);
```

### Progress Tracking

```php
$agent = OnboardingAgent::withFields(['name', 'email', 'company'], 'openai');
$result = $agent->beginConversation();

// Get progress information
$progress = $agent->getProgress($result->sessionId);
echo "Current field: " . $progress->currentField;
echo "Progress: " . $progress->progressPercentage . "%";
echo "Is complete: " . ($progress->isComplete ? 'Yes' : 'No');

// Check if onboarding is complete
if ($agent->isComplete($result->sessionId)) {
    $data = $agent->completeOnboarding($result->sessionId);
}
```

### Conversation History

```php
$agent = OnboardingAgent::withFields(['name', 'email', 'company'], 'openai');
$result = $agent->beginConversation();

// Send some messages
$agent->chat("Hi, I'm John", $result->sessionId);
$agent->chat("john@example.com", $result->sessionId);

// Get conversation history
$history = $agent->getConversationHistory($result->sessionId);

// History contains ChatMessage objects with role, content, and sessionId
foreach ($history as $message) {
    echo $message->role . ": " . $message->content . "\n";
}
```

## Step 7: Customization

### Custom AI Behavior

Extend the OnboardingAgent class:

```php
<?php

namespace App\Services;

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingSessionResult;

class CustomOnboardingAgent extends OnboardingAgent
{
    public function beginConversation(?string $sessionId = null): OnboardingSessionResult
    {
        // Call parent method
        $result = parent::beginConversation($sessionId);
        
        // Customize the first message
        $customMessage = "Welcome! I'm your personal onboarding assistant. Let's get started!";
        
        return OnboardingSessionResult::make(
            $result->success,
            $result->sessionId,
            $customMessage
        );
    }
    
    protected function getAIInstructions(array $fields): string
    {
        $baseInstructions = parent::getAIInstructions($fields);
        
        // Add custom instructions
        return $baseInstructions . "\n\nAdditional instructions: Be extra friendly and use emojis when appropriate.";
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
   - Ensure the model configuration exists in `config/ai-onboarding.php`

3. **"No fields configured" error**
   - Make sure to call `configureFields()` before `beginConversation()`
   - Or use `OnboardingAgent::withFields()` helper method

4. **Session management issues**
   - Always pass the session ID when working with multiple sessions
   - Use the returned `OnboardingSessionResult` object to get the session ID

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

## API Reference

### OnboardingAgent Methods

- `__construct(?string $aiModel = null)` - Create new instance
- `configureFields(array $fields): self` - Set fields to collect
- `beginConversation(?string $sessionId = null): OnboardingSessionResult` - Start session
- `chat(string $userMessage, ?string $sessionId = null): ChatMessage` - Send message
- `completeOnboarding(?string $sessionId = null): array` - Get extracted data
- `getProgress(?string $sessionId = null): OnboardingProgress` - Get progress info
- `isComplete(?string $sessionId = null): bool` - Check if complete
- `getConversationHistory(?string $sessionId = null): array` - Get chat history

### Facade Helpers

- `OnboardingAgent::create(?string $aiModel = null)` - Create instance
- `OnboardingAgent::withFields(array $fields, ?string $aiModel = null)` - Create with fields 