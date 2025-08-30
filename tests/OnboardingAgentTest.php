<?php

namespace ElliotPutt\LaravelAiOnboarding\Tests;

use Orchestra\Testbench\TestCase;
use ElliotPutt\LaravelAiOnboarding\OnboardingAgent;

class OnboardingAgentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing sessions
        OnboardingAgent::clearSession();
    }
    
    protected function tearDown(): void
    {
        // Clean up after each test
        OnboardingAgent::clearSession();
        parent::tearDown();
    }

    public function test_can_start_session()
    {
        $sessionId = OnboardingAgent::startSession();
        
        $this->assertNotEmpty($sessionId);
        $this->assertIsString($sessionId);
    }

    public function test_can_set_fields()
    {
        $fields = ['name', 'email', 'company'];
        
        OnboardingAgent::setFields($fields);
        
        // Note: Since fields are protected, we can't directly test them
        // but we can verify the method doesn't throw an exception
        $this->assertTrue(true);
    }

    public function test_can_get_history()
    {
        $sessionId = OnboardingAgent::startSession();
        
        $history = OnboardingAgent::getHistory($sessionId);
        
        $this->assertIsArray($history);
        $this->assertEmpty($history); // Should be empty for new session
    }

    public function test_can_clear_session()
    {
        $sessionId = OnboardingAgent::startSession();
        
        // Should not throw an exception
        OnboardingAgent::clearSession($sessionId);
        
        $this->assertTrue(true);
    }

    public function test_session_management()
    {
        // Test multiple sessions
        $session1 = OnboardingAgent::startSession('test-1');
        $session2 = OnboardingAgent::startSession('test-2');
        
        $this->assertNotEquals($session1, $session2);
        $this->assertEquals('test-1', $session1);
        $this->assertEquals('test-2', $session2);
    }
} 