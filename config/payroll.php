<?php

return [
    'payslip_password' => [
        'default_generation_rule' => env('PAYSLIP_PASSWORD_RULE', 'tax_id_last4+birth_year'),
    ],
];
