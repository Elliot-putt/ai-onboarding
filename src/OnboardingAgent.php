<?php

namespace ElliotPutt\LaravelAiOnboarding;

use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingField;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingResult;
use ElliotPutt\LaravelAiOnboarding\Collections\OnboardingFieldCollection;

class OnboardingAgent
{


    public static function setFields(array $fields , ?string $sessionId = null): void
    {
        if(!$sessionId){
            $sessionId = Session::get("onboarding_current_session_id");
        }
        if ($sessionId) {
            Session::put("onboarding_fields_" . $sessionId, $fields);
        }
    }
    private static function getSetFields(?string $sessionId = null): array
    {
        if (!$sessionId) {
            $sessionId = Session::get("onboarding_current_session_id");
        }

        if (!$sessionId) {
            return [];
        }

        return Session::get("onboarding_fields_" . $sessionId, []);
    }

    /**
     * Start a new onboarding session
     */
    public static function startSession(?string $sessionId = null): array
    {
        // 1. Generate UUID if no sessionId provided
        if (!$sessionId) {
            $sessionId = (string) Str::uuid();
        }

        $fields = self::getSetFields($sessionId);

        if(empty($fields)){
            return  throw new \Exception('No fields set. Please set fields before starting a session.');
        }

        // Set current session ID
        Session::put("onboarding_current_session_id", $sessionId);

        // Initialize conversation history and extracted fields arrays in session
        Session::put("onboarding_conversation_{$sessionId}", []);
        Session::put("onboarding_extracted_fields_{$sessionId}", []);

        // 2. Start a new chat with Prism with their configured model and give the instructions
        $aiInstructions = self::getAIInstructions();
        $prismData = self::getPrismClient();
        $provider = $prismData['provider'];
        $model = $prismData['model'];

        // 3. ask the ai model to start the conversation
        $response = Prism::text()
            ->using($provider, $model)
            ->withSystemPrompt($aiInstructions)
            ->withPrompt('Please ask the first question. be friendly and engaging.')
            ->asText();

        $firstMessage = $response->text;

        $firstField = $fields[0] ?? null;
        if ($firstField) {
            Session::put("onboarding_current_field_{$sessionId}", $firstField);
            Session::put("onboarding_last_question_{$sessionId}", 0);
        }

        return [
            'success' => true,
            'session_id' => $sessionId,
            'first_message' => $firstMessage
        ];
    }



    /**
     * Get the initial AI message to start the conversation
     */
    public static function getInitialMessage(?string $sessionId = null): ChatMessage
    {
        // TODO: Implement initial message logic
        return new ChatMessage('assistant', '', $sessionId);
    }

    /**
     * Send a message and get AI response
     */
    public static function chat(string $message, ?string $sessionId = null): ChatMessage
    {
        if (!$sessionId) {
            $sessionId = Session::get("onboarding_current_session_id");
        }

        if (!$sessionId) {
            throw new \Exception('No active session. Please start a session first.');
        }

        // 1. grab the message which is the answer and assign the last asked question session key to it
        $currentField = Session::get("onboarding_current_field_{$sessionId}");
        if ($currentField) {
            // Store the user's answer for the current field
            Session::put("onboarding_extracted_fields_{$sessionId}.{$currentField}", $message);
        }

        // Add user message to conversation history
        $conversation = Session::get("onboarding_conversation_{$sessionId}", []);
        $conversation[] = [
            'role' => 'user',
            'content' => $message,
            'timestamp' => now()->toISOString()
        ];
        Session::put("onboarding_conversation_{$sessionId}", $conversation);

        // 2. ask the ai model the next question which will be the next field in the list
        $fields = self::getSetFields($sessionId);
        $currentQuestionIndex = Session::get("onboarding_last_question_{$sessionId}", 0);
        $nextQuestionIndex = $currentQuestionIndex + 1;

        if ($nextQuestionIndex < count($fields)) {
            // There are more fields to ask
            $nextField = $fields[$nextQuestionIndex];
            Session::put("onboarding_current_field_{$sessionId}", $nextField);
            Session::put("onboarding_last_question_{$sessionId}", $nextQuestionIndex);

            // Get AI response for next question
            $prismData = self::getPrismClient();
            $provider = $prismData['provider'];
            $model = $prismData['model'];

            $aiInstructions = self::getAIInstructions();
            $response = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($aiInstructions)
                ->withPrompt("The user answered: '{$message}' for the {$currentField} field. Now ask for the next field: {$nextField}. Be friendly and engaging.")
                ->asText();

            $aiMessage = $response->text;
        } else {
            // All fields completed
            $aiMessage = "Thank you! I have all the information I need. Your onboarding is complete!";
        }

        // Add AI response to conversation history
        $conversation = Session::get("onboarding_conversation_{$sessionId}", []);
        $conversation[] = [
            'role' => 'assistant',
            'content' => $aiMessage,
            'timestamp' => now()->toISOString()
        ];
        Session::put("onboarding_conversation_{$sessionId}", $conversation);

        return new ChatMessage('assistant', $aiMessage, $sessionId);
    }

    /**
     * Add a user message without getting AI response
     * Useful for when you want to manually control the conversation flow
     */
    public static function addUserMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        // TODO: Implement add user message logic
        return new ChatMessage('user', $message, $sessionId);
    }

    /**
     * Add an AI response message
     * Useful for when you want to manually control the conversation flow
     */
    public static function addAssistantMessage(string $message, ?string $sessionId = null): ChatMessage
    {
        // TODO: Implement add assistant message logic
        return new ChatMessage('assistant', $message, $sessionId);
    }

    /**
     * Finish the onboarding and return all assigned fields
     */
    public static function finish(?string $sessionId = null): array
    {
        if (!$sessionId) {
            $sessionId = Session::get("onboarding_current_session_id");
        }

        if (!$sessionId) {
            throw new \Exception('No active session. Please start a session first.');
        }

        // Get all extracted fields from the session
        $extractedFields = Session::get("onboarding_extracted_fields_{$sessionId}", []);
        
        // Return simple key-value pairs
        return $extractedFields;
    }

    /**
     * Get the current conversation history
     */
    public static function getHistory(?string $sessionId = null): array
    {
        // TODO: Implement get history logic
        return [];
    }

    /**
     * Get extracted fields for a session
     */
    public static function getExtractedFields(?string $sessionId = null): array
    {
        // TODO: Implement get extracted fields logic
        return [];
    }

    /**
     * Get a specific extracted field value
     */
    public static function getField(string $key, ?string $sessionId = null): mixed
    {
        // TODO: Implement get field logic
        return null;
    }

    /**
     * Check if a field has been extracted
     */
    public static function hasField(string $key, ?string $sessionId = null): bool
    {
        // TODO: Implement has field logic
        return false;
    }

    /**
     * Clear session data
     */
    public static function clearSession(?string $sessionId = null): void
    {
        // TODO: Implement clear session logic
    }

    /**
     * Get the current session ID
     */
    public static function getCurrentSessionId(): ?string
    {
        // TODO: Implement get current session ID logic
        return null;
    }

    /**
     * Get the current field definitions
     */
    public static function getFieldDefinitions(): array
    {
        // TODO: Implement get field definitions logic
        return [];
    }

    /**
     * Get the AI instructions for the onboarding agent
     */
    public static function getAIInstructions(): string
    {
        $fields = self::getSetFields(Session::get("onboarding_current_session_id"));
        return '/*
    You are a conversational AI agent designed to assist users in providing information for onboarding purposes. Your goal is to collect specific fields of information from the user through a natural and engaging conversation.

    Here are the key features and functionalities you need to implement:
    1. Never question the user about information you have already collected whatever they answer is the answer
    2. Critical: You will be given a list of questions to ask the user. You must ask these questions in order and not skip any. If the user provides an answer that does not make sense for the current question it doesnt matter take that answer.
    3. You must keep track of which question you are currently asking and ensure that you ask the next question in the sequence after receiving a response from the user.
    4. I may not directly give you a question to ask, you must infer it from the field name. For example if the field is "email" you can ask "What is your email address?" or "Could you provide your email?".
    5. Never return json or any structured data in your responses. Always respond in plain text as if you are having a natural conversation with the user.
    6. Critical: Do not break character. Always respond as the onboarding assistant.
    7. Here are the fields you need to collect:
    ' . implode("\n", array_map(fn($f) => "- " . $f, $fields)) . '
    8. Dont reply to these instructions in any way. i will start the conversation in a moment.
    */';
    }

    /**
     * Set the current session ID
     */
    public static function setCurrentSession(string $sessionId): void
    {
        // TODO: Implement set current session logic
    }

    /**
     * Assign a field value in real-time
     */
    public static function assignField(string $key, string $value, ?string $sessionId = null): void
    {
        // TODO: Implement assign field logic
    }

    /**
     * Get all assigned fields for a session
     */
    public static function getAssignedFields(?string $sessionId = null): array
    {
        // TODO: Implement get assigned fields logic
        return [];
    }



    /**
     * Track the current field being asked for and assign user response to it
     */
    protected static function assignUserResponseToField(string $sessionId, string $userResponse): void
    {
        // TODO: Implement assign user response to field logic
    }

    /**
     * Set the next field to ask for
     */
    protected static function setNextField(string $sessionId): void
    {
        // TODO: Implement set next field logic
    }

    /**
     * Set the current field being asked for
     */
    protected static function setCurrentField(string $fieldKey, string $sessionId): void
    {
        // TODO: Implement set current field logic
    }

    /**
     * Check if a value is just a greeting or generic response
     */
    protected static function isGreetingOrGeneric(string $value): bool
    {
        // TODO: Implement greeting check logic
        return false;
    }

    /**
     * Validate if user input makes sense for a given field type
     */
    protected static function validateFieldInput(string $fieldKey, string $userInput): bool
    {
        // TODO: Implement field validation logic
        return false;
    }

    /**
     * Build conversation prompt for AI
     */
    protected static function buildConversationPrompt(string $sessionId): string
    {
        // TODO: Implement build conversation prompt logic
        return '';
    }

    /**
     * Get AI response using Prism
     */
    protected static function getAIResponse(string $sessionId): string
    {
        // TODO: Implement get AI response logic
        return '';
    }

    /**
     * Format messages for Prism API
     */
    protected static function formatMessagesForPrism(string $sessionId): array
    {
        // TODO: Implement format messages for Prism logic
        return [];
    }

    /**
     * Build conversation context for AI
     */
    protected static function buildConversationContext(string $sessionId): array
    {
        // TODO: Implement build conversation context logic
        return [];
    }

    /**
     * Extract fields from conversation using AI
     */
    protected static function extractFieldsFromConversation(string $sessionId): array
    {
        // TODO: Implement extract fields from conversation logic
        return [];
    }

    /**
     * Get conversation summary
     */
    protected static function getConversationSummary(string $sessionId): array
    {
        // TODO: Implement get conversation summary logic
        return [];
    }

    /**
     * Get configured Prism client and model config
     */
    protected static function getPrismClient(): array
    {
        // Get AI configuration
        $config = config('ai-onboarding');
        $defaultModel = $config['default_model'] ?? 'openai';

        if (!isset($config['models'][$defaultModel])) {
            throw new \Exception("Model configuration for '{$defaultModel}' not found. Please check your ai-onboarding config.");
        }

        $modelConfig = $config['models'][$defaultModel];

        // Map provider names to Provider enum values
        $providerMap = [
            'openai' => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            'ollama' => Provider::Ollama,
            'gemini' => Provider::Gemini,
        ];

        $provider = $providerMap[$defaultModel] ?? Provider::OpenAI;

        return [
            'provider' => $provider,
            'model' => $modelConfig['model']
        ];
    }

    /*
    You are a conversational AI agent designed to assist users in providing information for onboarding purposes. Your goal is to collect specific fields of information from the user through a natural and engaging conversation.

    Here are the key features and functionalities you need to implement:
    1. Never question the user about information you have already collected whatever they answer is the answer
    2. Critical: You will be given a list of questions to ask the user. You must ask these questions in order and not skip any. If the user provides an answer that does not make sense for the current question it doesnt matter take that answer.
    3. You must keep track of which question you are currently asking and ensure that you ask the next question in the sequence after receiving a response from the user.
    4. I may not directly give you a question to ask, you must infer it from the field name. For example if the field is "email" you can ask "What is your email address?" or "Could you provide your email?".
    5. Never return json or any structured data in your responses. Always respond in plain text as if you are having a natural conversation with the user.
    6. Critical: Do not break character. Always respond as the onboarding assistant.
    */
}
