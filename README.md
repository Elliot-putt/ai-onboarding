# Laravel AI Onboarding

A powerful Laravel package that uses AI to conduct conversational onboarding sessions, extracting structured data through natural conversation.

## Features

- 🤖 **AI-Powered Conversations** - Uses OpenAI, Anthropic, Ollama, or Gemini
- 💬 **Natural Language Processing** - Extracts data through friendly chat
- 🔧 **Flexible Configuration** - Choose your AI model and fields
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

## Quick Start

### Modern API (Recommended)

```php
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

// Create agent with AI model
$agent = new OnboardingAgent('anthropic');

// Configure fields to collect
$agent->configureFields(['name', 'email', 'company', 'budget']);

// Start conversation
$result = $agent->beginConversation();
echo $result->firstMessage; // "Hi! I'd love to learn more about you. What's your name?"

// Chat with the AI
$response = $agent->chat('John Doe');
echo $response->content; // "Great to meet you, John! What's your email address?"

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

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).