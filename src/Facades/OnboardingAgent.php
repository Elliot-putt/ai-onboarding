<?php

namespace ElliotPutt\LaravelAiOnboarding\Facades;

use Illuminate\Support\Facades\Facade;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;

/**
 * @method static \ElliotPutt\LaravelAiOnboarding\OnboardingAgent setModel(?string $model)
 * @method static \ElliotPutt\LaravelAiOnboarding\OnboardingAgent configureFields(array $fields)
 * @method static \ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingSessionResult beginConversation(?string $sessionId = null)
 * @method static ChatMessage chat(string $userMessage, ?string $sessionId = null)
 * @method static array completeOnboarding(?string $sessionId = null)
 * @method static array getConversationHistory(?string $sessionId = null)
 * @method static bool isComplete(?string $sessionId = null)
 * @method static string|null getCurrentFieldName(?string $sessionId = null)
 * @method static \ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingProgress getProgress(?string $sessionId = null)
 */
class OnboardingAgent extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ai-onboarding-agent';
    }

    /**
     * Create a new OnboardingAgent instance with optional AI model
     */
    public static function create(?string $aiModel = null)
    {
        return new \ElliotPutt\LaravelAiOnboarding\OnboardingAgent($aiModel);
    }

    /**
     * Create a new OnboardingAgent instance with configured fields
     * 
     * @param array $config Can be:
     *   - Simple array: ['name', 'email', 'company'] (backward compatible)
     *   - New syntax: [
     *       'fields' => ['name', 'email', 'phone'],
     *       'rules' => [
     *           'email' => ['required', 'email'],
     *           'phone' => ['nullable', 'string']
     *       ]
     *     ]
     *   - Legacy format: [
     *       ['name' => 'email', 'rules' => ['required', 'email']],
     *       'name' // Simple field name still supported
     *     ]
     */
    public static function withFields(array $config, ?string $aiModel = null)
    {
        $agent = new \ElliotPutt\LaravelAiOnboarding\OnboardingAgent($aiModel);
        return $agent->configureFields($config);
    }
} 