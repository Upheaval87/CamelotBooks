<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reversal threshold for identity verification
    |--------------------------------------------------------------------------
    | When set, creating a reversal whose total amount is greater than or
    | equal to this value requires the user to confirm their identity in the
    | reversal modal (`identity_confirm`). When null, the gate is disabled.
    |
    */

    'identity_verify_threshold' => env('JOURNAL_REVERSAL_IDENTITY_THRESHOLD'),
];