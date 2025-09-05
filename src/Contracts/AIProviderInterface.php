<?php

namespace ElliotPutt\LaravelAiOnboarding\Contracts;

interface AIProviderInterface
{
    /**
     * Send a prompt to the AI and get response
     */
    public function generateResponse(string $systemPrompt, string $userPrompt): string;
    
    /**
     * Get the provider name
     */
    public function getName(): string;
    
    /**
     * Validate the provider configuration
     */
    public function validateConfig(array $config): bool;
}
