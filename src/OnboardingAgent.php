<?php

namespace ElliotPutt\LaravelAiOnboarding;

use Prism\Prism;
use Prism\Contracts\Client;
use Illuminate\Support\Str;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingResult;
use ElliotPutt\LaravelAiOnboarding\Collections\OnboardingFieldCollection;

class OnboardingAgent
{
    protected static array $conversationHistory = [];
    protected static array $extractedFields = [];
    protected static array $fieldDefinitions = [];
    protected static ?string $currentSessionId = null;

    /**
     * Start a new onboarding session
     */
    public static function startSession(?string $sessionId = null): string
    {
        $sessionId = $sessionId ?? Str::uuid()->toString();
        static::$currentSessionId = $sessionId;
        static::$conversationHistory[$sessionId] = [];
        static::$extractedFields[$sessionId] = [];
        
        return $sessionId;
    }

    /**
     * Set the fields you want extracted from the conversation
     * Accepts various formats for flexibility
     */
    public static function setFields(array|OnboardingFieldCollection $fields): void
    {
        if ($fields instanceof OnboardingFieldCollection) {
            static::$fieldDefinitions = $fields->toArray();
        } else {
            static::$fieldDefinitions = OnboardingFieldCollection::make($fields)->toArray();
        }
    }

    /**
     * Send a message and get AI response
     */
    public static function chat(string $message, ?string $sessionId = null): ChatMessage
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        
        if (!$sessionId) {
            throw new \Exception('No active session. Call startSession() first.');
        }

        // Add user message to history
        $userMessage = ChatMessage::make('user', $message, $sessionId);
        static::$conversationHistory[$sessionId][] = $userMessage;

        // Get AI response
        $aiResponse = static::getAIResponse($sessionId);
        
        // Add AI response to history
        $assistantMessage = ChatMessage::make('assistant', $aiResponse, $sessionId);
        static::$conversationHistory[$sessionId][] = $assistantMessage;

        return $assistantMessage;
    }

    /**
     * Add a user message without getting AI response
     * Useful for when you want to manually control the conversation flow
     */
    public static function addUserMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        
        if (!$sessionId) {
            throw new \Exception('No active session. Call startSession() first.');
        }

        $userMessage = ChatMessage::make('user', $message, $sessionId);
        static::$conversationHistory[$sessionId][] = $userMessage;

        return $userMessage;
    }

    /**
     * Add an AI response message
     * Useful for when you want to manually control the conversation flow
     */
    public static function addAssistantMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        
        if (!$sessionId) {
            throw new \Exception('No active session. Call startSession() first.');
        }

        $assistantMessage = ChatMessage::make('assistant', $message, $sessionId);
        static::$conversationHistory[$sessionId][] = $assistantMessage;

        return $assistantMessage;
    }

    /**
     * Finish the onboarding and extract all fields
     */
    public static function finish(?string $sessionId = null): OnboardingResult
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        
        if (!$sessionId) {
            throw new \Exception('No active session. Call startSession() first.');
        }

        if (empty(static::$fieldDefinitions)) {
            throw new \Exception('No fields defined. Call setFields() first.');
        }

        // Extract fields using AI
        $extractedFields = static::extractFieldsFromConversation($sessionId);
        
        return OnboardingResult::make(
            $sessionId,
            $extractedFields,
            static::getConversationSummary($sessionId),
            count(static::$conversationHistory[$sessionId]),
            static::$conversationHistory[$sessionId]
        );
    }

    /**
     * Get the current conversation history
     */
    public static function getHistory(?string $sessionId = null): array
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        return static::$conversationHistory[$sessionId] ?? [];
    }

    /**
     * Get extracted fields for a session
     */
    public static function getExtractedFields(?string $sessionId = null): array
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        return static::$extractedFields[$sessionId] ?? [];
    }

    /**
     * Get a specific extracted field value
     */
    public static function getField(string $key, ?string $sessionId = null): mixed
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        return static::$extractedFields[$sessionId][$key] ?? null;
    }

    /**
     * Check if a field has been extracted
     */
    public static function hasField(string $key, ?string $sessionId = null): bool
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        return isset(static::$extractedFields[$sessionId][$key]);
    }

    /**
     * Clear session data
     */
    public static function clearSession(?string $sessionId = null): void
    {
        $sessionId = $sessionId ?? static::$currentSessionId;
        
        if ($sessionId) {
            unset(static::$conversationHistory[$sessionId]);
            unset(static::$extractedFields[$sessionId]);
        }
    }

    /**
     * Get the current session ID
     */
    public static function getCurrentSessionId(): ?string
    {
        return static::$currentSessionId;
    }

    /**
     * Set the current session ID
     */
    public static function setCurrentSession(string $sessionId): void
    {
        static::$currentSessionId = $sessionId;
    }

    /**
     * Get AI response using Prism
     */
    protected static function getAIResponse(string $sessionId): string
    {
        $config = config('ai-onboarding');
        $model = $config['default_model'] ?? 'openai';
        $modelConfig = $config['models'][$model] ?? [];

        try {
            $prism = new Prism();
            $client = $prism->client($modelConfig['driver'] ?? 'openai');
            
            // Build conversation context
            $messages = static::buildConversationContext($sessionId);
            
            $response = $client->chat([
                'model' => $modelConfig['model'] ?? 'gpt-4',
                'messages' => $messages,
                'max_tokens' => 1000,
            ]);

            return $response->choices[0]->message->content ?? 'I apologize, but I couldn\'t generate a response.';
        } catch (\Exception $e) {
            return 'I apologize, but I encountered an error: ' . $e->getMessage();
        }
    }

    /**
     * Build conversation context for AI
     */
    protected static function buildConversationContext(string $sessionId): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful onboarding assistant. Ask relevant questions to gather information and be conversational. Keep responses concise but friendly.'
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

    /**
     * Extract fields from conversation using AI
     */
    protected static function extractFieldsFromConversation(string $sessionId): array
    {
        $config = config('ai-onboarding');
        $model = $config['default_model'] ?? 'openai';
        $modelConfig = $config['models'][$model] ?? [];

        try {
            $prism = new Prism();
            $client = $prism->client($modelConfig['driver'] ?? 'openai');
            
            // Build extraction prompt
            $fieldKeys = array_map(fn($field) => $field['key'], static::$fieldDefinitions);
            $fieldsList = implode(', ', $fieldKeys);
            $extractionPrompt = "Based on our conversation, please extract the following information and return ONLY valid JSON: {$fieldsList}";
            
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a data extraction specialist. Extract the requested information from the conversation and return ONLY valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $extractionPrompt
                ]
            ];

            // Add conversation history
            foreach (static::$conversationHistory[$sessionId] as $message) {
                $messages[] = [
                    'role' => $message->role,
                    'content' => $message->content
                ];
            }

            $response = $client->chat([
                'model' => $modelConfig['model'] ?? 'gpt-4',
                'messages' => $messages,
                'max_tokens' => 500,
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            
            // Try to parse JSON response
            $extracted = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                static::$extractedFields[$sessionId] = $extracted;
                return $extracted;
            }

            // Fallback: return empty fields
            $fallbackFields = array_fill_keys($fieldKeys, '');
            static::$extractedFields[$sessionId] = $fallbackFields;
            return $fallbackFields;

        } catch (\Exception $e) {
            // Fallback: return empty fields
            $fieldKeys = array_map(fn($field) => $field['key'], static::$fieldDefinitions);
            $fallbackFields = array_fill_keys($fieldKeys, '');
            static::$extractedFields[$sessionId] = $fallbackFields;
            return $fallbackFields;
        }
    }

    /**
     * Get conversation summary
     */
    protected static function getConversationSummary(string $sessionId): array
    {
        $history = static::$conversationHistory[$sessionId] ?? [];
        
        if (empty($history)) {
            return [
                'start_time' => null,
                'end_time' => null,
                'user_messages' => 0,
                'ai_messages' => 0,
            ];
        }
        
        return [
            'start_time' => $history[0]->timestamp ?? null,
            'end_time' => end($history)->timestamp ?? null,
            'user_messages' => count(array_filter($history, fn($m) => $m->isUser())),
            'ai_messages' => count(array_filter($history, fn($m) => $m->isAssistant())),
        ];
    }
} 