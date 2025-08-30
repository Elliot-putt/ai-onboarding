<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the default AI model and provider settings
    |
    */
    'default_model' => env('AI_ONBOARDING_DEFAULT_MODEL', 'openai'),
    
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

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the package routes
    |
    */
    'route_prefix' => env('AI_ONBOARDING_ROUTE_PREFIX', 'ai-onboarding'),
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Chat Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the chat behavior and appearance
    |
    */
    'chat' => [
        'max_messages' => env('AI_ONBOARDING_MAX_MESSAGES', 50),
        'session_timeout' => env('AI_ONBOARDING_SESSION_TIMEOUT', 3600), // 1 hour
        'welcome_message' => 'Hello! I\'m here to help you get started. Let me ask you a few questions to understand your needs better.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Extraction Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how fields are extracted from chat responses
    |
    */
    'field_extraction' => [
        'enabled' => true,
        'extraction_prompt' => 'Please extract the following information from our conversation and format it as JSON: {fields}',
        'fallback_values' => [],
    ],
]; 