<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Branch Request Pricing
    |--------------------------------------------------------------------------
    |
    | Flat per-branch unit price used by the default FlatBranchPricingService.
    | The service is bound behind the BranchPricingService interface in
    | AppServiceProvider, so a volume-tiered or per-company pricing strategy can
    | be swapped in without touching the controllers.
    |
    */

    'unit_price_per_branch' => (float) env('BRANCH_REQUEST_UNIT_PRICE', 5000.00),

    'tax_rate' => (float) env('BRANCH_REQUEST_TAX_RATE', 0.0),

    // How many days a quotation stays valid before the expiry command marks it expired.
    'validity_days' => (int) env('BRANCH_REQUEST_VALIDITY_DAYS', 14),

    // Prefix for the bank-reference used to match a payment to its quotation.
    'bank_reference_prefix' => 'BRQ-',
];
