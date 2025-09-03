<?php

/**
 * Example: New Clean Syntax for Field Configuration
 *
 * This example demonstrates the new, cleaner syntax for configuring
 * fields with validation rules.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

echo "=== New Clean Syntax Examples ===\n\n";

// Example 1: Your exact requested syntax
echo "1. Your Requested Syntax:\n";
$agent1 = new OnboardingAgent('anthropic');
$agent1->configureFields([
    'fields' => [
        'email',
        'phone'
    ],
    'rules' => [
        'email' => ['required', 'email'],
        'phone' => ['nullable', 'string'],
    ],
]);

echo "✓ Configured with clean syntax\n\n";

// Example 2: More comprehensive example
echo "2. Comprehensive Example:\n";
$agent2 = new OnboardingAgent('openai');
$agent2->configureFields([
    'fields' => [
        'website',
        'first_name',
        'last_name',
        'email_address',
        'telephone_number',
        'message',
        'property_id',
        'request_viewing'
    ],
    'rules' => [
        'website' => ['required', 'string'],
        'first_name' => ['required', 'string'],
        'last_name' => ['required', 'string'],
        'email_address' => ['required', 'email'],
        'telephone_number' => ['nullable', 'string', 'phone:GB'],
        'message' => ['required', 'string'],
        'property_id' => ['nullable', 'string'],
        'request_viewing' => ['nullable', 'boolean'],
    ],
]);

echo "✓ Configured with comprehensive validation rules\n\n";

// Example 3: Mixed fields (some with rules, some without)
echo "3. Mixed Fields Example:\n";
$agent3 = new OnboardingAgent('anthropic');
$agent3->configureFields([
    'fields' => [
        'name',           // No validation rules
        'email',          // Has validation rules
        'company',        // No validation rules
        'phone',          // Has validation rules
        'notes'           // No validation rules
    ],
    'rules' => [
        'email' => ['required', 'email'],
        'phone' => ['nullable', 'string', 'regex:/^[\+]?[1-9][\d]{0,15}$/']
    ]
]);

echo "✓ Configured with mixed fields (some validated, some not)\n\n";

// Example 4: Direct instantiation with new syntax
echo "4. Direct Instantiation:\n";
$agent4 = new OnboardingAgent('openai');
$agent4->configureFields([
    'fields' => [
        'customer_name',
        'email',
        'budget'
    ],
    'rules' => [
        'email' => ['required', 'email'],
        'budget' => ['required', 'numeric', 'min:1000']
    ]
]);

echo "✓ Created with direct instantiation using new syntax\n\n";

// Example 5: Backward compatibility still works
echo "5. Backward Compatibility:\n";
$agent5 = new OnboardingAgent('anthropic');

// Old simple array syntax still works
$agent5->configureFields(['name', 'email', 'company']);
echo "✓ Old simple array syntax still works\n";

// Legacy format still works
$agent5->configureFields([
    ['name' => 'email', 'rules' => ['required', 'email']],
    'name'
]);
echo "✓ Legacy format still works\n\n";

echo "=== All Examples Completed Successfully! ===\n";
echo "\nKey Benefits of New Syntax:\n";
echo "• ✅ Cleaner and more readable\n";
echo "• ✅ Separates field names from validation rules\n";
echo "• ✅ Easier to maintain and modify\n";
echo "• ✅ More intuitive for Laravel developers\n";
echo "• ✅ Full backward compatibility maintained\n";
echo "• ✅ Works with both direct instantiation and Facade (in Laravel)\n\n";

echo "Usage in your code:\n";
echo "```php\n";
echo "\$agent->configureFields([\n";
echo "    'fields' => [\n";
echo "        'email',\n";
echo "        'phone'\n";
echo "    ],\n";
echo "    'rules' => [\n";
echo "        'email' => ['required', 'email'],\n";
echo "        'phone' => ['nullable', 'string'],\n";
echo "    ],\n";
echo "]);\n";
echo "```\n";
