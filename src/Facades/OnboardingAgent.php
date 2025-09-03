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
     */
    public static function withFields(array $fields, ?string $aiModel = null)
    {
        $agent = new \ElliotPutt\LaravelAiOnboarding\OnboardingAgent($aiModel);
        return $agent->configureFields($fields);
    }
} 