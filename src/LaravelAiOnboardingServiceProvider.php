<?php

namespace ElliotPutt\LaravelAiOnboarding;

use Illuminate\Support\ServiceProvider;

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
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ai-onboarding.php' => config_path('ai-onboarding.php'),
        ], 'ai-onboarding-config');
    }
} 