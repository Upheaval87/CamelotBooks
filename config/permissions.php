<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Permissions
    |--------------------------------------------------------------------------
    | Each module defines the actions it supports. The seeder generates
    | permissions as "{module}.{action}" for every combination.
    |
    | Standard actions: view, create, edit, void, approve
    | Modules may define additional custom actions (e.g. "post", "close", "lock").
    |
    | "void" = delete/void a posted record (separate from edit)
    | "approve" = approve a pending record (supports segregation of duties)
    */

    'modules' => [
        // ── Financial Transactions ──
        'invoices'        => ['view', 'create', 'edit', 'void', 'approve', 'post'],
        'credit-notes'    => ['view', 'create', 'void', 'post'],
        'sales-receipts'  => ['view', 'create', 'edit', 'void', 'post'],
        'quotations'      => ['view', 'create', 'edit', 'void', 'approve', 'send', 'convert', 'email'],
        'sales-orders'    => ['view', 'create', 'edit', 'void', 'send', 'confirm', 'convert', 'cancel'],
        'bills'           => ['view', 'create', 'edit', 'void', 'approve', 'post'],
        'vendor-credits'  => ['view', 'create', 'void', 'post'],
        'expenses'        => ['view', 'create', 'edit', 'void', 'approve', 'post', 'submit', 'reject', 'return', 'pay', 'duplicate', 'delete'],
        'expense-claims'  => ['view', 'create', 'submit', 'approve', 'reject', 'reimburse', 'delete'],
        'expense-categories' => ['view', 'create', 'edit', 'delete'],
        'expense-recurring' => ['view', 'create', 'edit', 'delete', 'run'],

        // ── Customers & Vendors ──
        'customers'       => ['view', 'create', 'edit', 'void'],
        'vendors'         => ['view', 'create', 'edit', 'void'],

        // ── Products & Inventory ──
        'products'        => ['view', 'create', 'edit', 'void'],
        'item-categories' => ['view', 'create', 'edit', 'void'],
        'inventory-items' => ['view'],
        'stock-adjustments' => ['view', 'create', 'edit', 'void'],
        'stock-transfers' => ['view', 'create', 'edit', 'void'],
        'stock-counts'    => ['view', 'create', 'edit', 'void', 'approve'],
        'assemblies'      => ['view', 'create', 'edit', 'void'],
        'uom-conversions' => ['view', 'edit'],
        'landed-costs'    => ['view', 'create', 'edit', 'void', 'approve', 'post'],

        // ── Purchasing ──
        'purchase-requisitions' => ['view', 'create', 'edit', 'void', 'approve', 'submit', 'reject'],
        'purchase-orders'       => ['view', 'create', 'edit', 'void', 'approve', 'confirm', 'cancel'],
        'goods-received-notes'  => ['view', 'create', 'edit', 'void', 'post'],

        // ── Payments ──
        'customer-payments' => ['view', 'create', 'void'],
        'vendor-payments'   => ['view', 'create', 'void', 'submit', 'approve', 'reject'],

        // ── Banking ──
        'bank-accounts' => ['view', 'create', 'edit', 'void'],
        'deposits'       => ['view', 'create'],
        'cheques'        => ['view', 'create', 'edit', 'void'],
        'petty-cash'     => ['view', 'create', 'edit', 'void', 'expense', 'replenish', 'establish'],
        'bank-reconciliations' => ['view', 'create', 'edit', 'import', 'approve', 'complete', 'reverse', 'match', 'adjust'],

        // ── Accounting ──
        'chart-of-accounts'  => ['view', 'create', 'edit', 'void'],
        'journal-entries'    => ['view', 'create', 'edit', 'void', 'approve', 'reverse', 'post', 'submit', 'reject'],
        'accounting-periods' => ['view', 'create', 'close', 'lock', 'reopen'],
        'fiscal-years'       => ['view', 'create', 'close', 'reopen'],
        'cost-centers'       => ['view', 'create', 'edit', 'void'],
        'exchange-rates'     => ['view', 'create', 'edit', 'delete'],
        'recurring-journals' => ['view', 'create', 'edit', 'void'],
        'account-classification' => ['view', 'edit'],

        // ── Payroll ──
        'employees'    => ['view', 'create', 'edit', 'void'],
        'payroll-runs' => ['view', 'create', 'edit', 'void', 'approve', 'post', 'pay', 'send-payslips', 'remit'],
        'paye-tables'  => ['view', 'create', 'edit', 'void'],
        'pension-schemes' => ['view', 'create', 'edit', 'void'],

        // ── Fixed Assets ──
        'asset-categories' => ['view', 'create', 'edit', 'void'],
        'fixed-assets'     => ['view', 'create', 'edit', 'void'],
        'depreciation'     => ['view', 'create'],
        'asset-disposals'  => ['view', 'create', 'void'],
        'asset-transfers'  => ['view', 'create', 'void'],
        'asset-impairments' => ['view', 'create', 'void', 'reverse'],
        'asset-revaluations' => ['view', 'create', 'void'],
        'asset-usage'      => ['view', 'create'],

        // ── Budgets ──
        'budgets' => ['view', 'create', 'edit', 'void', 'approve', 'lock'],

        // ── POS ──
        'pos-sales'         => ['view', 'create', 'void'],
        'pos-terminals'     => ['view', 'create', 'edit', 'void'],
        'pos-payment-methods' => ['view', 'create', 'edit', 'void'],
        'pos-till-sessions' => ['view', 'create', 'close'],
        'pos-settlements'   => ['view', 'create'],
        'pos-returns'       => ['view', 'create', 'void'],

        // ── Administration ──
        'system-settings'    => ['view', 'edit'],
        'features'           => ['view', 'edit'],
        'users'              => ['view', 'create', 'edit', 'void'],
        'roles'              => ['view', 'create', 'edit', 'void'],
        'numbering-sequences' => ['view', 'create', 'edit', 'void', 'reset'],
        'audit-log'          => ['view', 'export'],
        'backups'            => ['view', 'create', 'restore', 'delete'],
        'security-settings'  => ['view', 'edit'],
        'notifications'      => ['view', 'edit'],
        'system-health'      => ['view'],
        'setup-wizard'       => ['view', 'manage'],
        'companies'          => ['view', 'create', 'edit'],
        'branches'           => ['view', 'create', 'edit', 'void'],
        'branch-requests'    => ['view', 'create', 'approve', 'reject', 'quote', 'confirm', 'cancel'],

        // ── Analytics & BI ──
        'analytics' => ['view'],
        'bi'        => ['view'],

        // ── Tax ──
        'tax-rates'  => ['view', 'create', 'edit', 'void'],
        'tax-returns' => ['view', 'create', 'submit'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Permissions
    |--------------------------------------------------------------------------
    | Every distinct report gets its own `reports.{key}.view` permission.
    | The Roles & Permissions UI will render these dynamically from this list.
    |
    | Reports with "summary" and "detail" variants get separate permissions
    | so a role can see aggregate figures without seeing sensitive line-item detail.
    */

    'reports' => [
        // General Ledger & Trial Balance
        'general_ledger'             => 'General Ledger',
        'trial_balance'              => 'Trial Balance',
        'trial_balance_comparison'   => 'Trial Balance Comparison',

        // Financial Statements
        'income_statement'           => 'Income Statement (P&L)',
        'balance_sheet'              => 'Balance Sheet',
        'cash_flow'                  => 'Cash Flow Statement',
        'equity_statement'           => 'Statement of Changes in Equity',
        'consolidated_balance_sheet' => 'Consolidated Balance Sheet',
        'consolidated_income_statement' => 'Consolidated Income Statement',

        // Accounts Receivable
        'ar_aging_summary'           => 'A/R Aging Summary',
        'ar_aging_detail'            => 'A/R Aging Detail',
        'customer_credit_balance'    => 'Customer Credit Balance',
        'customer_statement'         => 'Customer Statement',

        // Accounts Payable
        'ap_aging_summary'           => 'A/P Aging Summary',
        'ap_aging_detail'            => 'A/P Aging Detail',
        'vendor_credit_balance'      => 'Vendor Credit Balance',
        'vendor_statement'           => 'Vendor Statement',

        // Sales Reports
        'sales_by_customer'          => 'Sales by Customer',
        'sales_by_item'              => 'Sales by Item',
        'sales_register'             => 'Sales Register',
        'quotation_status'           => 'Quotation Status',
        'journal_report'             => 'Journal Report',

        // Purchasing Reports
        'purchase_register'          => 'Purchase Register',
        'purchases_by_vendor'        => 'Purchases by Vendor',
        'purchases_by_item'          => 'Purchases by Item',
        'po_status'                  => 'Purchase Order Status',
        'unbilled_receipts'          => 'Unbilled Receipts',

        // Inventory Reports
        'stock_movement'             => 'Stock Movement',
        'stock_count_variance'       => 'Stock Count Variance',
        'item_profitability'         => 'Item Profitability',
        'inventory_valuation'        => 'Inventory Valuation',
        'low_stock'                  => 'Low Stock Report',

        // Banking Reports
        'bank_balances'              => 'Bank Balances',
        'deposits_in_transit'        => 'Deposits in Transit',
        'cheque_register'            => 'Cheque Register',

        // Fixed Asset Reports
        'asset_revaluation'          => 'Asset Revaluation Report',
        'asset_impairment'           => 'Asset Impairment Report',
        'asset_disposal'             => 'Asset Disposal Report',
        'tax_depreciation_schedule'  => 'Tax Depreciation Schedule',

        // Payroll Reports
        'payroll_register'           => 'Payroll Register',
        'payroll_summary'            => 'Payroll Summary',
        'payroll_detail'             => 'Payroll Detail (Per-Employee)',
        'employee_cost_by_branch'    => 'Employee Cost by Branch',
        'paye_remittance'            => 'PAYE Remittance Report',
        'pension_remittance'         => 'Pension Remittance Report',
        'payslip_report'             => 'Payslip Report',

        // Compliance & Cross-Module
        'period_lock_status'         => 'Period Lock Status',
        'chart_of_accounts'          => 'Chart of Accounts',
        'pending_approvals_aging'    => 'Pending Approvals Aging',
        'eis_submission_status'      => 'EIS Submission Status',
        'assembly_build_history'     => 'Assembly Build History',
        'unbilled_deliveries'        => 'Unbilled Deliveries',

        // Cash
        'cash_position'              => 'Cash Position',

        // POS Reports
        'pos_x_report'               => 'POS X-Report',
        'pos_z_report'               => 'POS Z-Report',
        'pos_sales_by_terminal'      => 'POS Sales by Terminal',
        'pos_sales_by_cashier'       => 'POS Sales by Cashier',

        // Report Centre
        'report_center'              => 'Report Centre (Favourites)',

        // Cross-Module
        'pending_approvals_aging'    => 'Pending Approvals Aging',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Definitions
    |--------------------------------------------------------------------------
    | Each role lists the permissions it includes. Permissions can be:
    |   - Full module: "{module}.*" (grants all actions for that module)
    |   - Specific: "{module}.{action}"
    |   - Report: "reports.{key}.view"
    |   - Wildcard: "*" (all permissions — admin only)
    |
    | The seeder resolves wildcards and module-level grants at seed time.
    */

    'roles' => [
        'system_admin' => [
            'label' => 'System Admin',
            'scope' => 'global',           // no company_id (global)
            'permissions' => ['*'],
        ],

        'company_admin' => [
            'label' => 'Company Admin',
            'scope' => 'company',
            'permissions' => ['*'],
        ],

        'accountant' => [
            'label' => 'Accountant',
            'scope' => 'company',
            'permissions' => [
                // Full financial module access (view, create, edit on all)
                'invoices.*',
                'credit-notes.*',
                'sales-receipts.*',
                'quotations.*',
                'sales-orders.*',
                'bills.*',
                'vendor-credits.*',
                'expenses.*',
                'expense-claims.*',
                'expense-categories.*',
                'expense-recurring.*',
                'customers.*',
                'vendors.*',
                'products.*',
                'item-categories.*',
                'stock-adjustments.*',
                'stock-transfers.*',
                'stock-counts.*',
                'assemblies.*',
                'uom-conversions.*',
                'landed-costs.*',
                'purchase-requisitions.*',
                'purchase-orders.*',
                'goods-received-notes.*',
                'customer-payments.*',
                'vendor-payments.*',
                'bank-accounts.*',
                'deposits.*',
                'cheques.*',
                'petty-cash.*',
                'bank-reconciliations.*',
                'chart-of-accounts.*',
                'journal-entries.*',
                'cost-centers.*',
                'exchange-rates.*',
                'recurring-journals.*',
                'account-classification.*',
                'employees.*',
                'payroll-runs.*',
                'paye-tables.*',
                'pension-schemes.*',
                'asset-categories.*',
                'fixed-assets.*',
                'depreciation.*',
                'asset-disposals.*',
                'asset-transfers.*',
                'asset-impairments.*',
                'asset-revaluations.*',
                'asset-usage.*',
                'budgets.*',
                'tax-rates.*',
                'tax-returns.*',
                'accounting-periods.*',
                'fiscal-years.*',
                // Reports — most financial reports
                'reports.general_ledger.view',
                'reports.trial_balance.view',
                'reports.trial_balance_comparison.view',
                'reports.income_statement.view',
                'reports.balance_sheet.view',
                'reports.cash_flow.view',
                'reports.equity_statement.view',
                'reports.consolidated_balance_sheet.view',
                'reports.consolidated_income_statement.view',
                'reports.ar_aging_summary.view',
                'reports.ar_aging_detail.view',
                'reports.ap_aging_summary.view',
                'reports.ap_aging_detail.view',
                'reports.sales_by_customer.view',
                'reports.sales_by_item.view',
                'reports.sales_register.view',
                'reports.quotation_status.view',
                'reports.journal_report.view',
                'reports.purchase_register.view',
                'reports.purchases_by_vendor.view',
                'reports.purchases_by_item.view',
                'reports.po_status.view',
                'reports.unbilled_receipts.view',
                'reports.customer_credit_balance.view',
                'reports.vendor_credit_balance.view',
                'reports.customer_statement.view',
                'reports.vendor_statement.view',
                'reports.stock_movement.view',
                'reports.stock_count_variance.view',
                'reports.item_profitability.view',
                'reports.inventory_valuation.view',
                'reports.low_stock.view',
                'reports.bank_balances.view',
                'reports.deposits_in_transit.view',
                'reports.cheque_register.view',
                'reports.asset_revaluation.view',
                'reports.asset_impairment.view',
                'reports.asset_disposal.view',
                'reports.tax_depreciation_schedule.view',
                'reports.period_lock_status.view',
                'reports.chart_of_accounts.view',
                'reports.pending_approvals_aging.view',
                'reports.unbilled_deliveries.view',
                'reports.cash_position.view',
                'reports.report_center.view',
                // No payroll detail — accountant sees summary-level only
                'reports.payroll_register.view',
                'reports.payroll_summary.view',
                'reports.employee_cost_by_branch.view',
                'reports.paye_remittance.view',
                'reports.pension_remittance.view',
                // Read-only on system areas
                'audit-log.view',
                'audit-log.export',
                'system-health.view',
                'features.view',
            ],
        ],

        'bookkeeper' => [
            'label' => 'Bookkeeper / Clerk',
            'scope' => 'company',
            'permissions' => [
                // View, create, edit on transactional modules — NO void, NO approve
                'invoices.view', 'invoices.create', 'invoices.edit',
                'credit-notes.view', 'credit-notes.create',
                'sales-receipts.view', 'sales-receipts.create',
                'quotations.view', 'quotations.create', 'quotations.edit',
                'sales-orders.view', 'sales-orders.create', 'sales-orders.edit',
                'bills.view', 'bills.create', 'bills.edit',
                'vendor-credits.view', 'vendor-credits.create',
                'expenses.view', 'expenses.create', 'expenses.edit',
                'expense-claims.view', 'expense-claims.create', 'expense-claims.submit',
                'expense-categories.view',
                'expense-recurring.view',
                // Customers & Vendors
                'customers.view', 'customers.create', 'customers.edit',
                'vendors.view', 'vendors.create', 'vendors.edit',
                // Inventory
                'products.view', 'products.create', 'products.edit',
                'item-categories.view', 'item-categories.create', 'item-categories.edit',
                'stock-adjustments.view', 'stock-adjustments.create',
                'stock-transfers.view', 'stock-transfers.create',
                'stock-counts.view', 'stock-counts.create', 'stock-counts.edit',
                'assemblies.view', 'assemblies.create', 'assemblies.edit',
                'uom-conversions.view', 'uom-conversions.edit',
                // Purchasing
                'purchase-requisitions.view', 'purchase-requisitions.create', 'purchase-requisitions.edit',
                'purchase-orders.view', 'purchase-orders.create', 'purchase-orders.edit',
                'goods-received-notes.view', 'goods-received-notes.create',
                // Banking (view only)
                'bank-accounts.view',
                'bank-reconciliations.view',
                'cheques.view', 'cheques.create',
                'petty-cash.view', 'petty-cash.create',
                // Payments
                'customer-payments.view', 'customer-payments.create',
                'vendor-payments.view', 'vendor-payments.create',
                // Accounting (view only)
                'chart-of-accounts.view',
                'journal-entries.view', 'journal-entries.create', 'journal-entries.edit',
                'cost-centers.view',
                'exchange-rates.view',
                'recurring-journals.view',
                // Fixed Assets (view only)
                'asset-categories.view',
                'fixed-assets.view',
                // Reports — basic operational reports
                'reports.general_ledger.view',
                'reports.trial_balance.view',
                'reports.sales_register.view',
                'reports.journal_report.view',
                'reports.purchase_register.view',
                'reports.stock_movement.view',
                'reports.inventory_valuation.view',
                'reports.low_stock.view',
                'reports.cash_position.view',
                'reports.chart_of_accounts.view',
                'reports.report_center.view',
                // System (view only)
                'audit-log.view',
                'system-health.view',
                'numbering-sequences.view',
            ],
        ],

        'approver' => [
            'label' => 'Approver / Manager',
            'scope' => 'company',
            'permissions' => [
                // View broad + approve on relevant modules
                'invoices.view', 'invoices.approve',
                'quotations.view', 'quotations.approve',
                'sales-orders.view',
                'bills.view', 'bills.approve',
                'expenses.view', 'expenses.approve',
                'expense-claims.view', 'expense-claims.approve',
                'stock-counts.view', 'stock-counts.approve',
                'purchase-requisitions.view', 'purchase-requisitions.approve',
                'purchase-orders.view', 'purchase-orders.approve',
                'budgets.view', 'budgets.approve',
                'journal-entries.view', 'journal-entries.approve',
                'payroll-runs.view', 'payroll-runs.approve',
                // Customers, Vendors, Products — view only
                'customers.view',
                'vendors.view',
                'products.view',
                // Inventory
                'stock-adjustments.view',
                'stock-transfers.view',
                'assemblies.view',
                'landed-costs.view', 'landed-costs.approve',
                // Banking — view only
                'bank-accounts.view',
                'bank-reconciliations.view', 'bank-reconciliations.approve',
                // Accounting — view only
                'chart-of-accounts.view',
                'accounting-periods.view',
                'fiscal-years.view',
                'cost-centers.view',
                // Reports — full access to summary/approval-related reports
                'reports.general_ledger.view',
                'reports.trial_balance.view',
                'reports.income_statement.view',
                'reports.balance_sheet.view',
                'reports.cash_flow.view',
                'reports.equity_statement.view',
                'reports.ar_aging_summary.view',
                'reports.ap_aging_summary.view',
                'reports.pending_approvals_aging.view',
                'reports.sales_by_customer.view',
                'reports.sales_by_item.view',
                'reports.sales_register.view',
                'reports.quotation_status.view',
                'reports.purchase_register.view',
                'reports.purchases_by_vendor.view',
                'reports.po_status.view',
                'reports.bank_balances.view',
                'reports.period_lock_status.view',
                'reports.payroll_summary.view',
                'reports.employee_cost_by_branch.view',
                'reports.report_center.view',
                // System — minimal
                'audit-log.view',
                'system-health.view',
            ],
        ],

        'cashier' => [
            'label' => 'Cashier / POS Operator',
            'scope' => 'company',
            'permissions' => [
                // POS only
                'pos-sales.view', 'pos-sales.create',
                'pos-till-sessions.view', 'pos-till-sessions.create', 'pos-till-sessions.close',
                'pos-returns.view', 'pos-returns.create',
                'pos-settlements.view',
                // POS reports
                'reports.pos_x_report.view',
                'reports.pos_z_report.view',
                'reports.pos_sales_by_terminal.view',
                'reports.pos_sales_by_cashier.view',
                // Customer lookup (read-only)
                'customers.view',
                // Product lookup (read-only)
                'products.view',
                // Basic inventory view
                'inventory-items.view',
                // System — minimal
                'system-health.view',
            ],
        ],

        'auditor' => [
            'label' => 'Auditor',
            'scope' => 'company',
            'permissions' => [
                // View everything across all modules
                'invoices.view',
                'credit-notes.view',
                'sales-receipts.view',
                'quotations.view',
                'sales-orders.view',
                'bills.view',
                'vendor-credits.view',
                'expenses.view',
                'expense-claims.view',
                'expense-categories.view',
                'expense-recurring.view',
                'customers.view',
                'vendors.view',
                'products.view',
                'item-categories.view',
                'inventory-items.view',
                'stock-adjustments.view',
                'stock-transfers.view',
                'stock-counts.view',
                'assemblies.view',
                'uom-conversions.view',
                'landed-costs.view',
                'purchase-requisitions.view',
                'purchase-orders.view',
                'goods-received-notes.view',
                'customer-payments.view',
                'vendor-payments.view',
                'bank-accounts.view',
                'deposits.view',
                'cheques.view',
                'petty-cash.view',
                'bank-reconciliations.view',
                'chart-of-accounts.view',
                'journal-entries.view',
                'accounting-periods.view',
                'fiscal-years.view',
                'cost-centers.view',
                'exchange-rates.view',
                'recurring-journals.view',
                'account-classification.view',
                'employees.view',
                'payroll-runs.view',
                'paye-tables.view',
                'pension-schemes.view',
                'asset-categories.view',
                'fixed-assets.view',
                'depreciation.view',
                'asset-disposals.view',
                'asset-transfers.view',
                'asset-impairments.view',
                'asset-revaluations.view',
                'asset-usage.view',
                'budgets.view',
                'tax-rates.view',
                'tax-returns.view',
                'pos-sales.view',
                'pos-terminals.view',
                'pos-payment-methods.view',
                'pos-till-sessions.view',
                'pos-settlements.view',
                'pos-returns.view',
                // System admin areas (view only)
                'system-settings.view',
                'features.view',
                'users.view',
                'roles.view',
                'numbering-sequences.view',
                'audit-log.view',
                'audit-log.export',
                'backups.view',
                'security-settings.view',
                'notifications.view',
                'system-health.view',
                'companies.view',
                'branches.view',
                'branch-requests.view',
                // Analytics & BI (view only)
                'analytics.view',
                'bi.view',

                // ALL reports — view only (auditors see everything including detail-level)
                'reports.*',
            ],
        ],

        'billing' => [
            'label' => 'Billing',
            'scope' => 'company',
            'permissions' => [
                // Narrow: branch-request billing lifecycle only
                'branch-requests.view',
                'branch-requests.quote',
                'branch-requests.confirm',
                // Read-only on customers, invoices, quotations for verification
                'customers.view',
                'invoices.view',
                'quotations.view',
                'sales-orders.view',
                // Read-only on system areas relevant to billing
                'branches.view',
                'audit-log.view',
                'system-health.view',
            ],
        ],

        'viewer' => [
            'label' => 'Viewer (Read-Only)',
            'scope' => 'company',
            'permissions' => [
                // View only on major modules
                'invoices.view',
                'credit-notes.view',
                'sales-receipts.view',
                'quotations.view',
                'sales-orders.view',
                'bills.view',
                'vendor-credits.view',
                'expenses.view',
                'expense-claims.view',
                'expense-categories.view',
                'expense-recurring.view',
                'customers.view',
                'vendors.view',
                'products.view',
                'inventory-items.view',
                'stock-adjustments.view',
                'stock-transfers.view',
                'stock-counts.view',
                'assemblies.view',
                'bank-accounts.view',
                'bank-reconciliations.view',
                'chart-of-accounts.view',
                'journal-entries.view',
                'accounting-periods.view',
                'fiscal-years.view',
                'cost-centers.view',
                'employees.view',
                'payroll-runs.view',
                'budgets.view',
                'branch-requests.view',
                // Reports — all read-only
                'reports.*',
                // System — minimal
                'audit-log.view',
                'system-health.view',
            ],
        ],
    ],
];