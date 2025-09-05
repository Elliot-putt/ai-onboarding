<?php

namespace ElliotPutt\LaravelAiOnboarding;

use Illuminate\Support\ServiceProvider;
use ElliotPutt\LaravelAiOnboarding\Services\AIProviderRegistry;

class LaravelAiOnboardingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai-onboarding.php', 'ai-onboarding'
        );

        // Register the OnboardingAgent factory
        $this->app->bind('ai-onboarding-agent', function ($app) {
            return new OnboardingAgent();
        });
        
        // Register the AI Provider Registry
        $this->app->singleton(AIProviderRegistry::class);
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ai-onboarding.php' => config_path('ai-onboarding.php'),
        ], 'ai-onboarding-config');
    }
} 