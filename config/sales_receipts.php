<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Overpayment policy for receipt-from-invoice settlement
    |--------------------------------------------------------------------------
    |
    | When a payment amount exceeds the invoice's outstanding balance:
    |   'cap'            - (default) the applied amount is capped at the invoice
    |                      balance; no excess is recorded.
    |   'customer_credit' - the excess is posted to a Customer Credit liability
    |                      (CR the 2200-series/`customer_credit` account), leaving
    |                      a credit on the customer's account for later use.
    |
    | The 'customer_credit' mode requires a mapped `customer_credit` account
    | (fallback: an account with code '2200'). If none can be resolved the
    | system falls back to 'cap' and records an audit note.
    |
    */
    'overpayment_policy' => env('SALES_RECEIPT_OVERPAYMENT_POLICY', 'cap'),

    /*
    | Account code used to resolve the Customer Credit liability when the
    | overpayment policy is 'customer_credit' and no `customer_credit`
    | mapping key exists.
    |
    */
    'customer_credit_account_code' => '2200',
];
