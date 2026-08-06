<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verification code settings
    |--------------------------------------------------------------------------
    |
    | Controls the 6-digit verification codes used by the code-based
    | password reset flow (forgot-password → verify-code → reset-password).
    |
    */

    'code_length' => (int) env('VERIFICATION_CODE_LENGTH', 6),

    'ttl_seconds' => (int) env('VERIFICATION_CODE_TTL_SECONDS', 600),

    'resend_cooldown_seconds' => (int) env('VERIFICATION_CODE_RESEND_COOLDOWN', 30),

];
