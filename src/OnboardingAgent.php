<?php

namespace ElliotPutt\LaravelAiOnboarding;

use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;

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
            Session::put("onboarding_last_asked_question_{$sessionId}", $firstMessage);
        }

        return [
            'success' => true,
            'session_id' => $sessionId,
            'first_message' => $firstMessage
        ];
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

        // Add user message to conversation history
        $conversation = Session::get("onboarding_conversation_{$sessionId}", []);
        $conversation[] = [
            'role' => 'user',
            'content' => $message,
            'timestamp' => now()->toISOString()
        ];
        Session::put("onboarding_conversation_{$sessionId}", $conversation);

        // Get current field and last asked question
        $currentField = Session::get("onboarding_current_field_{$sessionId}");
        $lastAskedQuestion = Session::get("onboarding_last_asked_question_{$sessionId}");

        // Validate the user's answer
        $isValidAnswer = self::validateUserAnswer($lastAskedQuestion, $message);

        if (!$isValidAnswer) {
            // User didn't provide a valid answer, ask the question again with better prompting
            $prismData = self::getPrismClient();
            $provider = $prismData['provider'];
            $model = $prismData['model'];

            $aiInstructions = self::getAIInstructions();
            $response = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($aiInstructions)
                ->withPrompt("The user responded with: '{$message}' to the question about {$currentField}, but this doesn't seem like a proper answer. Please ask the question again in a more engaging way and explain why you need this information. Make sure to ask the same question again at the end of your response.")
                ->asText();

            $aiMessage = $response->text;
            
            // Update the last asked question
            Session::put("onboarding_last_asked_question_{$sessionId}", $aiMessage);
        } else {
            // Valid answer, store it and move to next question
            if ($currentField) {
                Session::put("onboarding_extracted_fields_{$sessionId}.{$currentField}", $message);
            }

            // Move to next question
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
                
                // Update the last asked question
                Session::put("onboarding_last_asked_question_{$sessionId}", $aiMessage);
            } else {
                // All fields completed
                $aiMessage = "Thank you! I have all the information I need. Your onboarding is complete!";
            }
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
     * Get the AI instructions for the validation agent
     */
    public static function getValidationAIInstructions(): string
    {
        return '/*
    You are a validation AI agent. Your ONLY job is to determine if a user\'s response is a valid answer to a specific question.

    RULES:
    1. You will be given a question and a user\'s response
    2. You must respond with ONLY the word "true" or "false" - nothing else
    3. Return "true" if the user provided a reasonable answer to the question
    4. Return "false" if the user did not provide a valid answer (e.g., said "no", "I don\'t know", "skip", etc.)
    5. Be strict - only accept actual answers, not refusals or non-answers

    Examples:
    - Question: "What is your name?" Response: "John" → "true"
    - Question: "What is your name?" Response: "no" → "false"
    - Question: "What is your email?" Response: "john@example.com" → "true"
    - Question: "What is your email?" Response: "I don\'t want to provide it" → "false"
    */';
    }

    /**
     * Validate if user's answer is a proper response to the question
     */
    public static function validateUserAnswer(string $question, string $userAnswer): bool
    {
        $prismData = self::getPrismClient();
        $provider = $prismData['provider'];
        $model = $prismData['model'];

        $validationInstructions = self::getValidationAIInstructions();
        $prompt = "Question: {$question}\nUser Response: {$userAnswer}";

        $response = Prism::text()
            ->using($provider, $model)
            ->withSystemPrompt($validationInstructions)
            ->withPrompt($prompt)
            ->asText();

        $validationResult = trim(strtolower($response->text));
        
        // Convert to boolean using filter_var as requested
        return filter_var($validationResult, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
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


}
