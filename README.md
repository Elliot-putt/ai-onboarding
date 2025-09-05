# Laravel AI Onboarding

A powerful Laravel package that uses AI to conduct conversational onboarding sessions, extracting structured data through natural conversation.

## Features

- 🤖 **AI-Powered Conversations** - Uses OpenAI, Anthropic, Ollama, Gemini, or your custom AI model
- 💬 **Natural Language Processing** - Extracts data through friendly chat with intelligent question generation
- 🧠 **Smart AI Assistant** - AI creates natural questions from field names and helps users when they ask questions
- 🔧 **Flexible Configuration** - Choose your AI model and fields with custom provider support
- 📊 **Progress Tracking** - Monitor onboarding completion
- 🎯 **Type Safety** - Full DTO support with IDE autocomplete
- 🔄 **Modern API** - Clean instance-based methods with full type safety
- 🏗️ **Clean Architecture** - Interfaces, traits, and proper separation of concerns

## Installation

```bash
composer require elliotputt/laravel-ai-onboarding
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ai-onboarding-config
```

## Configuration

Configure your AI models in `config/ai-onboarding.php`:

```php
return [
    'default_model' => 'openai',
    
    'models' => [
        'openai' => [
            'model' => 'gpt-4',
            'api_key' => env('OPENAI_API_KEY'),
        ],
        'anthropic' => [
            'model' => 'claude-3-sonnet-20240229',
            'api_key' => env('ANTHROPIC_API_KEY'),
        ],
        'ollama' => [
            'model' => 'llama2',
            'base_url' => 'http://localhost:11434',
        ],
        'gemini' => [
            'model' => 'gemini-2.0-flash',
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
    ],
];
```

## Custom AI Providers

The package supports custom AI providers, allowing you to integrate your own AI services seamlessly. This is perfect for companies with proprietary AI models or specific API requirements.

### Setting Up a Custom Provider

1. **Create your AI provider class** that implements `AIProviderInterface`:

```php
<?php

namespace App\Providers;

use ElliotPutt\LaravelAiOnboarding\Contracts\AIProviderInterface;
use App\Services\YourAIService;

class YourAIProvider implements AIProviderInterface
{
    public function __construct(
        private YourAIService $aiService
    ) {}

    public function generateResponse(string $systemPrompt, string $userPrompt): string
    {
        $response = $this->aiService->generateContent([
            'context' => $systemPrompt,
            'message' => $userPrompt,
        ]);

        return $response['content'] ?? '';
    }

    public function getName(): string
    {
        return 'your-ai-provider';
    }

    public function validateConfig(array $config): bool
    {
        return isset($config['api_key']) && isset($config['base_uri']);
    }
}
```

2. **Register your provider** in your `AppServiceProvider`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ElliotPutt\LaravelAiOnboarding\Services\AIProviderRegistry;
use App\Providers\YourAIProvider;
use App\Services\YourAIService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(AIProviderRegistry::class, function (AIProviderRegistry $registry) {
            $registry->register('custom', new YourAIProvider(
                app(YourAIService::class)
            ));
        });
    }
}
```

3. **Configure your AI service** in `config/services.php`:

```php
'your-ai-service' => [
    'api_key' => env('YOUR_AI_API_KEY'),
    'base_uri' => env('YOUR_AI_BASE_URI'),
],
```

4. **Set the custom provider** in your `.env`:

```env
AI_ONBOARDING_CUSTOM_PROVIDER=App\Providers\YourAIProvider
```

### How It Works

- The package automatically detects when a custom provider is configured
- It uses your custom provider instead of the built-in Prism models
- Your provider receives the same system prompts and user messages as built-in providers
- The AI creates natural, engaging questions from field names
- Users can ask questions about fields and get helpful guidance

### Example: StreetAI Integration

Here's a complete example using a custom StreetAI provider:

```php
// App\Providers\StreetAIProvider.php
class StreetAIProvider implements AIProviderInterface
{
    public function generateResponse(string $systemPrompt, string $userPrompt): string
    {
        $response = $this->aiService->generateContent([
            'context' => $systemPrompt,
            'message' => $userPrompt,
        ]);

        return data_get($response, 'response', 'No response from AI service.');
    }
}

// Usage remains exactly the same
$agent = new OnboardingAgent();
$agent->configureFields(['name', 'email', 'company']);
$result = $agent->beginConversation();
```

## Quick Start

### Modern API with Laravel Validation (Recommended)

```php
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

// Create agent with AI model
$agent = new OnboardingAgent('anthropic');

// Configure fields with Laravel validation rules using the new clean syntax
$agent->configureFields([
    'fields' => [
        'name',
        'email',
        'phone',
        'budget'
    ],
    'rules' => [
        'email' => ['required', 'email'],
        'phone' => ['nullable', 'string', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
        'budget' => ['required', 'numeric', 'min:1000', 'max:100000']
    ]
]);

// Start conversation
$result = $agent->beginConversation();
echo $result->firstMessage; // "Hi there! Welcome—I'm here to help get you started. To begin, could you please tell me your name?"

// Chat with the AI (automatically validates with both AI and Laravel rules)
$response = $agent->chat('John Doe');
echo $response->content; // "Thanks, John! Could you please share your email address with me?"

// AI can also help users when they ask questions
$helpResponse = $agent->chat('What should I put for email?');
echo $helpResponse->content; // "No problem! For the email field, you should provide the email address you use for work or personal communication. It usually looks like 'yourname@example.com'. Could you please share your email address?"

// Check progress
$progress = $agent->getProgress();
echo "Progress: {$progress->progressPercentage}%"; // "Progress: 25%"

// Complete onboarding (all data validated)
$data = $agent->completeOnboarding();
// Returns: ['name' => 'John Doe', 'email' => 'john@example.com', ...]
```

### Simple API (Backward Compatible)

```php
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

// Create agent with AI model
$agent = new OnboardingAgent('anthropic');

// Configure fields to collect (simple format)
$agent->configureFields(['name', 'email', 'company', 'budget']);

// Start conversation
$result = $agent->beginConversation();
echo $result->firstMessage; // "Hi there! Welcome—I'm here to help get you started. To begin, could you please tell me your name?"

// Chat with the AI
$response = $agent->chat('John Doe');
echo $response->content; // "Thanks, John! Could you please share your email address with me?"

// Check progress
$progress = $agent->getProgress();
echo "Progress: {$progress->progressPercentage}%"; // "Progress: 25%"

// Complete onboarding
$data = $agent->completeOnboarding();
// Returns: ['name' => 'John Doe', 'email' => 'john@example.com', ...]
```

### Using the Facade

```php
use ElliotPutt\LaravelAiOnboarding\Facades\OnboardingAgent;

// Factory method - create with fields (recommended)
$agent = OnboardingAgent::withFields(['name', 'email', 'company'], 'openai');
$result = $agent->beginConversation();

// Alternative: Create instance and configure separately
$agent = OnboardingAgent::create('anthropic');
$agent->configureFields(['name', 'email', 'company']);
$result = $agent->beginConversation();
```

### Type-Safe DTOs

All methods return structured DTOs for better type safety and IDE support:

```php
$agent = new OnboardingAgent('anthropic');
$agent->configureFields(['name', 'email', 'company']);

$result = $agent->beginConversation();
// $result is OnboardingSessionResult with ->success, ->sessionId, ->firstMessage

$response = $agent->chat('John Doe');
// $response is ChatMessage with ->role, ->content, ->timestamp, ->sessionId

$progress = $agent->getProgress();
// $progress is OnboardingProgress with ->progressPercentage, ->isComplete, etc.
```

## API Reference

### Instance Methods

#### `new OnboardingAgent(?string $aiModel = null)`
Create a new agent instance with optional AI model.

#### `configureFields(array $fields): self`
Set the fields to collect during onboarding.

#### `beginConversation(?string $sessionId = null): OnboardingSessionResult`
Start a new onboarding session. Returns structured result with session info.

#### `chat(string $userMessage, ?string $sessionId = null): ChatMessage`
Send a message and get AI response. Returns structured chat message.

#### `completeOnboarding(?string $sessionId = null): array`
Finish onboarding and return all extracted data.

#### `getProgress(?string $sessionId = null): OnboardingProgress`
Get detailed progress information including completion percentage.

#### `getConversationHistory(?string $sessionId = null): array`
Retrieve the full conversation history.

#### `isComplete(?string $sessionId = null): bool`
Check if onboarding is complete.

### DTOs

#### `OnboardingSessionResult`
```php
$result->success;        // bool
$result->sessionId;      // string
$result->firstMessage;   // string
```

#### `OnboardingProgress`
```php
$progress->currentField;        // string|null
$progress->currentIndex;        // int
$progress->totalFields;         // int
$progress->progressPercentage;  // float
$progress->isComplete;          // bool
$progress->getRemainingFields(); // int
$progress->isFirstField();      // bool
$progress->isLastField();       // bool
```

#### `ChatMessage`
```php
$message->role;      // string
$message->content;   // string
$message->timestamp; // string
$message->sessionId; // string|null
$message->isUser();  // bool
$message->isAssistant(); // bool
$message->toArray(); // array
```

## Validation System

The package now supports **dual validation** - combining AI validation with Laravel validation rules for robust data collection.

### How It Works

1. **AI Validation**: Always performed to ensure the user's response is a valid answer to the question
2. **Laravel Validation**: Performed when validation rules are defined for a field
3. **Both must pass** for the field to be accepted

### Field Configuration Options

#### New Clean Syntax (Recommended)
The new syntax separates field names from validation rules, making it much cleaner and easier to read:

```php
$agent->configureFields([
    'fields' => [
        'email',
        'phone'
    ],
    'rules' => [
        'email' => ['required', 'email'],
        'phone' => ['nullable', 'string'],
    ],
]);
```

**Benefits:**
- ✅ **Cleaner separation** of field names and validation rules
- ✅ **More readable** and easier to maintain
- ✅ **Intuitive** for Laravel developers
- ✅ **Flexible** - only define rules for fields that need validation
- ✅ **Backward compatible** with all existing formats

#### Simple Fields (Backward Compatible)
```php
$agent->configureFields(['name', 'email', 'company']);
```

#### Fields with Validation Rules (New Clean Syntax)
```php
$agent->configureFields([
    'fields' => [
        'email',
        'phone',
        'budget'
    ],
    'rules' => [
        'email' => ['required', 'email'],
        'phone' => ['nullable', 'string', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
        'budget' => ['required', 'numeric', 'min:1000', 'max:100000']
    ]
]);
```


### Supported Laravel Validation Rules

All standard Laravel validation rules are supported:

- `required`, `nullable`
- `string`, `numeric`, `integer`, `boolean`
- `email`, `url`, `date`, `regex`
- `min:value`, `max:value`, `between:min,max`
- `in:value1,value2`, `not_in:value1,value2`
- `size:value`, `digits:value`
- Custom validation rules and closures

### Validation Error Handling

When validation fails, the AI will:
1. Explain what went wrong
2. Ask for the information again
3. Provide helpful guidance based on the validation error

```php
// User enters invalid email
$response = $agent->chat('not-an-email');
// AI: "That doesn't look like a valid email address. Please provide a proper email like john@example.com"
```

## Advanced Usage

### Custom AI Model Per Instance

```php
$customerAgent = new OnboardingAgent('anthropic');
$employeeAgent = new OnboardingAgent('openai');

$customerAgent->configureFields(['name', 'email', 'phone']);
$employeeAgent->configureFields(['name', 'email', 'department', 'start_date']);
```

### Progress Tracking

```php
$agent = new OnboardingAgent();
$agent->configureFields(['name', 'email', 'company', 'budget']);

$result = $agent->beginConversation();
$response = $agent->chat('John Doe');

$progress = $agent->getProgress();
if ($progress->isComplete) {
    $data = $agent->completeOnboarding();
} else {
    echo "Still need: {$progress->getRemainingFields()} more fields";
}
```

### Conversation History

```php
$history = $agent->getConversationHistory();
foreach ($history as $message) {
    echo "[{$message->timestamp}] {$message->role}: {$message->content}";
}
```

## Laravel Integration

### In Controllers

```php
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

class OnboardingController extends Controller
{
    public function start(Request $request)
    {
        $agent = new OnboardingAgent('anthropic');
        $agent->configureFields(['name', 'email', 'company']);
        
        $result = $agent->beginConversation();
        
        return response()->json([
            'session_id' => $result->sessionId,
            'message' => $result->firstMessage
        ]);
    }
    
    public function chat(Request $request)
    {
        $agent = new OnboardingAgent('anthropic');
        $agent->configureFields(['name', 'email', 'company']);
        $response = $agent->chat($request->message, $request->session_id);
        
        return response()->json([
            'message' => $response->content,
            'progress' => $agent->getProgress($request->session_id)->toArray()
        ]);
    }
}
```

### Dependency Injection

```php
use ElliotPutt\LaravelAiOnboarding\Interfaces\OnboardingAgentCoreInterface;

class OnboardingService
{
    public function __construct(
        private OnboardingAgentCoreInterface $agent
    ) {}
    
    public function startOnboarding(): array
    {
        $this->agent->configureFields(['name', 'email']);
        return $this->agent->beginConversation()->toArray();
    }
}
```

## Testing

```php
use ElliotPutt\LaravelAiOnboarding\Interfaces\OnboardingAgentCoreInterface;

class OnboardingTest extends TestCase
{
    public function test_onboarding_flow()
    {
        $mockAgent = $this->createMock(OnboardingAgentCoreInterface::class);
        $mockAgent->expects($this->once())
            ->method('chat')
            ->with('John Doe')
            ->willReturn(new ChatMessage('assistant', 'What is your email?', 'session-123'));
            
        // Test your code with the mock
    }
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).