<?php

/**
 * Demo Script for Laravel AI Onboarding Package (Library Version)
 * 
 * This script demonstrates the basic functionality of the refactored package.
 * Note: This is for demonstration purposes only - in a real Laravel app,
 * you would use the proper Laravel integration.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Simulate Laravel environment
if (!function_exists('config')) {
    function config($key, $default = null) {
        $config = [
            'ai-onboarding.default_model' => 'openai',
            'ai-onboarding.models.openai.driver' => 'openai',
            'ai-onboarding.models.openai.api_key' => 'demo-key',
            'ai-onboarding.models.openai.model' => 'gpt-4',
        ];
        
        return $config[$key] ?? $default;
    }
}

if (!function_exists('now')) {
    function now() {
        return new DateTime();
    }
}

echo "🚀 Laravel AI Onboarding Package - Library Version Demo\n";
echo "=====================================================\n\n";

try {
    // Note: This demo won't actually work without proper AI API keys
    // but shows the intended usage pattern
    
    echo "1. Starting onboarding session...\n";
    // $sessionId = OnboardingAgent::startSession();
    echo "   ✓ Session started (demo mode)\n\n";
    
    echo "2. Setting fields to extract using DTOs...\n";
    echo "   ✓ Fields configured with type safety\n";
    echo "   • customer_name (required, string)\n";
    echo "   • company_name (required, string)\n";
    echo "   • job_title (optional, string)\n";
    echo "   • primary_goal (required, string)\n";
    echo "   • budget_range (optional, string)\n";
    echo "   • timeline (optional, string)\n\n";
    
    echo "3. Simulating chat conversation...\n";
    echo "   User: Hi, I'm John Smith from TechCorp\n";
    echo "   AI: Hello John! Welcome to our platform. What brings you here today?\n";
    echo "   User: I'm looking for a project management solution for my team\n";
    echo "   AI: That sounds great! How large is your team and what are your main challenges?\n";
    echo "   User: We're about 15 people and struggle with task tracking\n";
    echo "   ✓ Chat conversation simulated\n\n";
    
    echo "4. Extracting fields from conversation...\n";
    $extractedData = [
        'customer_name' => 'John Smith',
        'company_name' => 'TechCorp',
        'job_title' => 'Team Lead',
        'primary_goal' => 'Project management solution for task tracking',
        'budget_range' => 'Not specified',
        'timeline' => 'Not specified'
    ];
    echo "   ✓ Fields extracted successfully\n\n";
    
    echo "5. Final results:\n";
    foreach ($extractedData as $field => $value) {
        echo "   • {$field}: {$value}\n";
    }
    
    echo "\n🎉 Demo completed successfully!\n";
    echo "\nKey Features of the Library Version:\n";
    echo "• ✅ Pure library package - no web routes or views\n";
    echo "• ✅ Static methods for easy integration\n";
    echo "• ✅ Full DTO support with type safety\n";
    echo "• ✅ Multiple session management\n";
    echo "• ✅ Manual conversation control\n";
    echo "• ✅ Automatic field extraction\n";
    echo "• ✅ No database dependencies\n\n";
    
    echo "To use this package in a real Laravel application:\n";
    echo "1. Install via composer: composer require elliotputt/laravel-ai-onboarding\n";
    echo "2. Set your AI API keys in .env\n";
    echo "3. Use the OnboardingAgent class or facade in your controllers\n";
    echo "4. Integrate into your own chat interface or API endpoints\n";
    echo "5. Access extracted data through typed DTOs\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n"; 