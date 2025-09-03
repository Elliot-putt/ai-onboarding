<?php

namespace ElliotPutt\LaravelAiOnboarding\Enums;

enum SessionKeys: string
{
    case CURRENT_SESSION_ID = 'onboarding_current_session_id';
    case FIELDS = 'onboarding_fields_';
    case CONVERSATION = 'onboarding_conversation_';
    case EXTRACTED_FIELDS = 'onboarding_extracted_fields_';
    case CURRENT_FIELD = 'onboarding_current_field_';
    case LAST_QUESTION = 'onboarding_last_question_';
    case LAST_ASKED_QUESTION = 'onboarding_last_asked_question_';

    /**
     * Get the session key with the session ID appended
     */
    public function withSessionId(string $sessionId): string
    {
        return $this->value . $sessionId;
    }
}
