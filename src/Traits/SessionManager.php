<?php

namespace ElliotPutt\LaravelAiOnboarding\Traits;

use Illuminate\Support\Facades\Session;
use ElliotPutt\LaravelAiOnboarding\Enums\SessionKeys;

trait SessionManager
{
    /**
     * Set onboarding fields for a session
     */
    protected function setSessionFields(array $fields, ?string $sessionId = null): void
    {
        if (!$sessionId) {
            $sessionId = Session::get(SessionKeys::CURRENT_SESSION_ID->value);
        }
        
        if ($sessionId) {
            Session::put(SessionKeys::FIELDS->withSessionId($sessionId), $fields);
        }
    }

    /**
     * Get onboarding fields for a session
     */
    protected function getSessionFields(?string $sessionId = null): array
    {
        if (!$sessionId) {
            $sessionId = Session::get(SessionKeys::CURRENT_SESSION_ID->value);
        }

        if (!$sessionId) {
            return [];
        }

        return Session::get(SessionKeys::FIELDS->withSessionId($sessionId), []);
    }

    /**
     * Set the current session ID
     */
    protected function setCurrentSessionId(string $sessionId): void
    {
        Session::put(SessionKeys::CURRENT_SESSION_ID->value, $sessionId);
    }

    /**
     * Get the current session ID
     */
    protected function getCurrentSessionId(): ?string
    {
        return Session::get(SessionKeys::CURRENT_SESSION_ID->value);
    }

    /**
     * Initialize session data for a new onboarding session
     */
    protected function initializeSessionData(string $sessionId): void
    {
        Session::put(SessionKeys::CONVERSATION->withSessionId($sessionId), []);
        Session::put(SessionKeys::EXTRACTED_FIELDS->withSessionId($sessionId), []);
    }

    /**
     * Add message to conversation history
     */
    protected function addToConversation(string $sessionId, string $role, string $content): void
    {
        $conversation = Session::get(SessionKeys::CONVERSATION->withSessionId($sessionId), []);
        $conversation[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toISOString()
        ];
        Session::put(SessionKeys::CONVERSATION->withSessionId($sessionId), $conversation);
    }

    /**
     * Get conversation history
     */
    protected function getConversation(string $sessionId): array
    {
        return Session::get(SessionKeys::CONVERSATION->withSessionId($sessionId), []);
    }

    /**
     * Set current field being asked
     */
    protected function setCurrentField(string $sessionId, string $field): void
    {
        Session::put(SessionKeys::CURRENT_FIELD->withSessionId($sessionId), $field);
    }

    /**
     * Get current field being asked
     */
    protected function getCurrentField(string $sessionId): ?string
    {
        return Session::get(SessionKeys::CURRENT_FIELD->withSessionId($sessionId));
    }

    /**
     * Set last question index
     */
    protected function setLastQuestionIndex(string $sessionId, int $index): void
    {
        Session::put(SessionKeys::LAST_QUESTION->withSessionId($sessionId), $index);
    }

    /**
     * Get last question index
     */
    protected function getLastQuestionIndex(string $sessionId): int
    {
        return Session::get(SessionKeys::LAST_QUESTION->withSessionId($sessionId), 0);
    }

    /**
     * Set last asked question
     */
    protected function setLastAskedQuestion(string $sessionId, string $question): void
    {
        Session::put(SessionKeys::LAST_ASKED_QUESTION->withSessionId($sessionId), $question);
    }

    /**
     * Get last asked question
     */
    protected function getLastAskedQuestion(string $sessionId): ?string
    {
        return Session::get(SessionKeys::LAST_ASKED_QUESTION->withSessionId($sessionId));
    }

    /**
     * Store extracted field value
     */
    protected function storeExtractedField(string $sessionId, string $field, string $value): void
    {
        Session::put(SessionKeys::EXTRACTED_FIELDS->withSessionId($sessionId) . ".{$field}", $value);
    }

    /**
     * Get all extracted fields
     */
    protected function getExtractedFields(string $sessionId): array
    {
        return Session::get(SessionKeys::EXTRACTED_FIELDS->withSessionId($sessionId), []);
    }

    /**
     * Ensure session exists or throw exception
     */
    protected function ensureSessionExists(?string $sessionId = null): string
    {
        if (!$sessionId) {
            $sessionId = $this->getCurrentSessionId();
        }

        if (!$sessionId) {
            throw new \Exception('No active session. Please start a session first.');
        }

        return $sessionId;
    }
}
