<?php

return [

    // Saved per-company BI settings (bi_settings table) override these defaults.
    // Each entry is keyed by (group_key.key) so callers can merge saved
    // values over the factory defaults with a single array_replace_recursive.

    'defaults' => [

        // Cost-allocation pools and their default driver.
        'allocation' => [
            'pools' => [
                'payroll' => [
                    'label' => 'Payroll & Benefits',
                    'accountsOut' => null, // resolved from default mappings / income-statement headings
                    'driver' => 'revenue_share',
                    'drivers' => ['revenue_share', 'headcount'],
                ],
                'occupancy' => [
                    'label' => 'Occupancy & Facilities',
                    'accountsOut' => null,
                    'driver' => 'floor_area',
                    'drivers' => ['floor_area', 'revenue_share'],
                ],
                'g_and_a' => [
                    'label' => 'General & Administration',
                    'accountsOut' => null,
                    'driver' => 'revenue_share',
                    'drivers' => ['revenue_share', 'headcount'],
                ],
            ],
            'default_floor_area_sqm' => 100,
        ],

        // Assumption-led metrics. All default to a salient neutral value; the
        // UI lets a privileged user override them per company.
        'assumptions' => [
            'cac_threshold' => 0,              // 0 => customer-acquisition-cost comparison disabled
            'wacc' => 0,                       // 0 => present-value / economic-value-add disabled
            'holding_cost_rate' => 0.10,       // annual % of weighted-average inventory value
            'retention_window_days' => 90,     // churn look-back window
        ],

        // Cash-scenario multipliers applied to the 13-week baseline inflows /
        // outflows per branch, plus per-branch expected-collection sliders.
        'scenario' => [
            'bull' => ['inflow' => 1.05, 'outflow' => 0.95],
            'base' => ['inflow' => 1.00, 'outflow' => 1.00],
            'bear' => ['inflow' => 0.90, 'outflow' => 1.10],
            'default_lead_time_days' => 14,
            'inflow_aging' => [0, 15, 30, 60, 90], // expected collection ladder (days after invoice)
        ],

        // Hurdles used by product / supplier scoring.
        'pricing' => [
            'abc_a_cumulative' => 0.80,   // grade A holds <= 80% of cumulative revenue
            'abc_b_cumulative' => 0.95,   // grade B holds <= 95%
            'supplier_quality_weight' => 0.35,
            'supplier_ontime_weight' => 0.40,
            'supplier_price_weight' => 0.25,
        ],
    ],

    // Heuristic classification used when a break-even account has no explicit
    // bi_expense_classes row. Keyword matching is case-insensitive against the
    // account NAME; names matching neither list default to VARIABLE.
    'expense_classes' => [
        'variable_keywords' => [
            'cogs', 'cost of good', 'consumable', 'material', 'freight',
            'commission', 'variable', 'packaging', 'delivery', 'shipping',
            'direct labour', 'direct material',
        ],
        'fixed_keywords' => [
            'rent', 'lease', 'salary', 'wage', 'depreciation', 'amortisation',
            'amortization', 'insurance', 'utilities', 'electricity', 'water',
            'security', 'internet', 'software', 'subscription', 'telephone',
            'professional', 'interest', 'loan',
        ],
    ],

];