<?php

namespace ElliotPutt\LaravelAiOnboarding\Traits;

use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

trait AIInteraction
{
    protected ?string $aiModel = null;

    /**
     * Set the AI model to use for this instance
     */
    public function setModel(?string $model): self
    {
        $this->aiModel = $model;
        return $this;
    }

    /**
     * Get the AI model for this instance
     */
    public function getModel(): ?string
    {
        return $this->aiModel;
    }

    /**
     * Get configured Prism client and model config
     */
    protected function getPrismClient(): array
    {
        // Get AI configuration
        $config = config('ai-onboarding');
        
        // Use instance model if set, otherwise use default
        $modelKey = $this->aiModel ?? $config['default_model'] ?? 'openai';

        if (!isset($config['models'][$modelKey])) {
            throw new \Exception("Model configuration for '{$modelKey}' not found. Please check your ai-onboarding config.");
        }

        $modelConfig = $config['models'][$modelKey];

        // Map provider names to Provider enum values
        $providerMap = [
            'openai' => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            'ollama' => Provider::Ollama,
            'gemini' => Provider::Gemini,
        ];

        $provider = $providerMap[$modelKey] ?? Provider::OpenAI;

        return [
            'provider' => $provider,
            'model' => $modelConfig['model']
        ];
    }

    /**
     * Send a prompt to the AI and get response
     */
    protected function getAIResponse(string $systemPrompt, string $userPrompt): string
    {
        $prismData = $this->getPrismClient();
        $provider = $prismData['provider'];
        $model = $prismData['model'];

        $response = Prism::text()
            ->using($provider, $model)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($userPrompt)
            ->asText();

        return $response->text;
    }

    /**
     * Get AI instructions for onboarding agent
     */
    protected function getAIInstructions(array $fields): string
    {
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
     * Get AI instructions for validation agent
     */
    protected function getValidationAIInstructions(): string
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
    protected function validateUserAnswer(string $question, string $userAnswer): bool
    {
        $validationInstructions = $this->getValidationAIInstructions();
        $prompt = "Question: {$question}\nUser Response: {$userAnswer}";

        $response = $this->getAIResponse($validationInstructions, $prompt);
        $validationResult = trim(strtolower($response));
        
        // Convert to boolean using filter_var as requested
        return filter_var($validationResult, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
