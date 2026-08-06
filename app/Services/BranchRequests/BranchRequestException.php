<?php

namespace App\Services\BranchRequests;

use RuntimeException;

/**
 * Raised when a branch-request lifecycle transition is invalid (open request
 * already exists, payment recorded against a non-quoted request, confirm on an
 * already-fulfilled request, etc.). Carries a stable error_code so the
 * controller can map it to a user-facing message.
 */
class BranchRequestException extends RuntimeException
{
    public const CODE_OPEN_REQUEST_EXISTS = 'open_request_exists';
    public const CODE_NOT_QUOTED = 'not_quoted';
    public const CODE_ALREADY_FULFILLED = 'already_fulfilled';
    public const CODE_INVALID_STATE = 'invalid_state';
    public const CODE_CASH_RESTRICTED = 'cash_restricted';
    public const CODE_CASH_NOTES_REQUIRED = 'cash_notes_required';
    public const CODE_CONFIRM_FORBIDDEN = 'confirm_forbidden';
    public const CODE_INVALID_AMOUNT = 'invalid_amount';
    public const CODE_QUOTE_EXPIRED = 'quote_expired';

    public function __construct(string $message, private readonly string $errorCode)
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
