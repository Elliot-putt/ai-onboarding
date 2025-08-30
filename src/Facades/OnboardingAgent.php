<?php

namespace ElliotPutt\LaravelAiOnboarding\Facades;

use Illuminate\Support\Facades\Facade;
use ElliotPutt\LaravelAiOnboarding\DTOs\ChatMessage;
use ElliotPutt\LaravelAiOnboarding\DTOs\OnboardingResult;
use ElliotPutt\LaravelAiOnboarding\Collections\OnboardingFieldCollection;

/**
 * @method static string startSession(string $sessionId = null)
 * @method static void setFields(array|OnboardingFieldCollection $fields)
 * @method static ChatMessage chat(string $message, string $sessionId = null)
 * @method static ChatMessage addUserMessage(string $message, string $sessionId = null)
 * @method static ChatMessage addAssistantMessage(string $message, string $sessionId = null)
 * @method static OnboardingResult finish(string $sessionId = null)
 * @method static array getHistory(string $sessionId = null)
 * @method static array getExtractedFields(string $sessionId = null)
 * @method static mixed getField(string $key, string $sessionId = null)
 * @method static bool hasField(string $key, string $sessionId = null)
 * @method static void clearSession(string $sessionId = null)
 * @method static string|null getCurrentSessionId()
 * @method static void setCurrentSession(string $sessionId)
 */
class OnboardingAgent extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ai-onboarding-agent';
    }
} 