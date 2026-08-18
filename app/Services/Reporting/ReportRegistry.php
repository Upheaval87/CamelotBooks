<?php

namespace App\Services\Reporting;

use Illuminate\Support\Facades\Route;

class ReportRegistry
{
    private static ?array $reports = null;

    private const CATEGORIES = [
        'financial_statements' => 'Financial Statements',
        'sales' => 'Sales / Accounts Receivable',
        'purchasing' => 'Purchasing / Accounts Payable',
        'inventory' => 'Inventory',
        'banking' => 'Banking',
        'fixed_assets' => 'Fixed Assets',
        'payroll' => 'Payroll',
        'budgeting' => 'Budgeting',
        'compliance' => 'Compliance & Audit',
        'analytics' => 'Analytics',
        'bi' => 'Business Intelligence',
        'pos' => 'Point of Sale',
    ];

    private static function all(): array
    {
        if (self::$reports !== null) {
            return self::$reports;
        }

        self::$reports = [
            // ── Financial Statements ──
            'trial_balance' => [
                'key' => 'trial_balance',
                'name' => 'Trial Balance',
                'description' => 'Debit and credit balances for all accounts at a point in time.',
                'category' => 'financial_statements',
                'route' => 'accounting.trial-balance.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'general_ledger' => [
                'key' => 'general_ledger',
                'name' => 'General Ledger',
                'description' => 'Detailed transaction history for all accounts.',
                'category' => 'financial_statements',
                'route' => 'accounting.general-ledger.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'income_statement' => [
                'key' => 'income_statement',
                'name' => 'Income Statement',
                'description' => 'Revenue and expenses for a period (Profit & Loss).',
                'category' => 'financial_statements',
                'route' => 'accounting.income-statement.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'balance_sheet' => [
                'key' => 'balance_sheet',
                'name' => 'Balance Sheet',
                'description' => 'Assets, liabilities, and equity at a point in time.',
                'category' => 'financial_statements',
                'route' => 'accounting.balance-sheet.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'equity_statement' => [
                'key' => 'equity_statement',
                'name' => 'Statement of Changes in Equity',
                'description' => 'Movements in equity accounts over a period.',
                'category' => 'financial_statements',
                'route' => 'accounting.equity-statement.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'cash_flow' => [
                'key' => 'cash_flow',
                'name' => 'Cash Flow Statement',
                'description' => 'Cash inflows and outows by operating, investing, and financing activities.',
                'category' => 'financial_statements',
                'route' => 'accounting.cash-flow.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'journal_report' => [
                'key' => 'journal_report',
                'name' => 'Journal Report',
                'description' => 'Formal listing of journal entries for a period with line details.',
                'category' => 'financial_statements',
                'route' => 'accounting.reports.journal',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'trial_balance_comparison' => [
                'key' => 'trial_balance_comparison',
                'name' => 'Trial Balance Comparison',
                'description' => 'Side-by-side trial balance for two periods with variance.',
                'category' => 'financial_statements',
                'route' => 'accounting.reports.trial-balance-comparison',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],

            // ── Sales / Accounts Receivable ──
            'ar_aging' => [
                'key' => 'ar_aging',
                'name' => 'A/R Aging',
                'description' => 'Outstanding customer invoices by age bracket.',
                'category' => 'sales',
                'route' => 'accounting.aging.ar-summary',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'sales_register' => [
                'key' => 'sales_register',
                'name' => 'Sales Register',
                'description' => 'All posted invoices, POS sales, and receipts for a period.',
                'category' => 'sales',
                'route' => 'accounting.sales-register.index',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'sales_by_customer' => [
                'key' => 'sales_by_customer',
                'name' => 'Sales by Customer',
                'description' => 'Revenue totals grouped by customer for a period.',
                'category' => 'sales',
                'route' => 'accounting.reports.sales-by-customer',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'sales_by_item' => [
                'key' => 'sales_by_item',
                'name' => 'Sales by Item',
                'description' => 'Revenue totals grouped by product/service for a period.',
                'category' => 'sales',
                'route' => 'accounting.reports.sales-by-item',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'customer_credit_balance' => [
                'key' => 'customer_credit_balance',
                'name' => 'Customer Credit Balances',
                'description' => 'Open/unapplied credit note balances per customer.',
                'category' => 'sales',
                'route' => 'accounting.reports.customer-credit-balance',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'quotation_status' => [
                'key' => 'quotation_status',
                'name' => 'Quotation Status',
                'description' => 'Open quotations with conversion status and pipeline view.',
                'category' => 'sales',
                'route' => 'accounting.reports.quotation-status',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'quotation_register' => [
                'key' => 'quotation_register',
                'name' => 'Quotation Register',
                'description' => 'Every quotation for a period with totals and status.',
                'category' => 'sales',
                'route' => 'accounting.reports.quotation-register',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'sales_pipeline' => [
                'key' => 'sales_pipeline',
                'name' => 'Sales Pipeline',
                'description' => 'Quotation funnel by status with win-rate and open-quote aging.',
                'category' => 'sales',
                'route' => 'accounting.reports.sales-pipeline',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'sales_receipts_daily_summary' => [
                'key' => 'sales_receipts_daily_summary',
                'name' => 'Sales Receipts Daily Summary',
                'description' => 'Daily totals of posted sales receipts for a period.',
                'category' => 'sales',
                'route' => 'accounting.reports.sales-receipts.daily-summary',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'sales_receipts_cashbook' => [
                'key' => 'sales_receipts_cashbook',
                'name' => 'Sales Receipts Cashbook',
                'description' => 'Cashbook listing of posted sales receipts with payment method.',
                'category' => 'sales',
                'route' => 'accounting.reports.sales-receipts.cashbook',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],

            // ── Purchasing / Accounts Payable ──
            'ap_aging' => [
                'key' => 'ap_aging',
                'name' => 'A/P Aging',
                'description' => 'Outstanding vendor bills by age bracket.',
                'category' => 'purchasing',
                'route' => 'accounting.aging.ap-summary',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'purchase_register' => [
                'key' => 'purchase_register',
                'name' => 'Purchase Register',
                'description' => 'All posted bills for a period.',
                'category' => 'purchasing',
                'route' => 'accounting.reports.purchase-register',
                'permission' => 'view_reports',
                'feature_flag' => ['purchasing'],
            ],
            'purchases_by_vendor' => [
                'key' => 'purchases_by_vendor',
                'name' => 'Purchases by Vendor',
                'description' => 'Purchase totals grouped by vendor for a period.',
                'category' => 'purchasing',
                'route' => 'accounting.reports.purchases-by-vendor',
                'permission' => 'view_reports',
                'feature_flag' => ['purchasing'],
            ],
            'purchases_by_item' => [
                'key' => 'purchases_by_item',
                'name' => 'Purchases by Item',
                'description' => 'Purchase totals grouped by product for a period.',
                'category' => 'purchasing',
                'route' => 'accounting.reports.purchases-by-item',
                'permission' => 'view_reports',
                'feature_flag' => ['purchasing'],
            ],
            'unbilled_receipts' => [
                'key' => 'unbilled_receipts',
                'name' => 'Unbilled Receipts',
                'description' => 'GRNs received but not yet billed — reconciles to Accrued Purchases.',
                'category' => 'purchasing',
                'route' => 'accounting.reports.unbilled-receipts',
                'permission' => 'view_reports',
                'feature_flag' => ['purchasing'],
            ],
            'po_status' => [
                'key' => 'po_status',
                'name' => 'Purchase Order Status',
                'description' => 'Open POs with received-vs-ordered quantity per line.',
                'category' => 'purchasing',
                'route' => 'accounting.reports.po-status',
                'permission' => 'view_reports',
                'feature_flag' => ['purchasing'],
            ],
            'vendor_credit_balance' => [
                'key' => 'vendor_credit_balance',
                'name' => 'Vendor Credit Balances',
                'description' => 'Open/unapplied vendor credit balances.',
                'category' => 'purchasing',
                'route' => 'accounting.reports.vendor-credit-balance',
                'permission' => 'view_reports',
                'feature_flag' => ['purchasing'],
            ],

            // ── Inventory ──
            'inventory_valuation' => [
                'key' => 'inventory_valuation',
                'name' => 'Inventory Valuation',
                'description' => 'FIFO-based inventory value by product and branch.',
                'category' => 'inventory',
                'route' => 'accounting.invsetup.valuation',
                'permission' => 'view_inventory',
                'feature_flag' => ['inventory'],
            ],
            'inventory_valuation_by_category' => [
                'key' => 'inventory_valuation_by_category',
                'name' => 'Inventory Valuation by Category',
                'description' => 'FIFO-based inventory value grouped by item category.',
                'category' => 'inventory',
                'route' => 'accounting.invsetup.valuation',
                'permission' => 'view_inventory',
                'feature_flag' => ['inventory'],
            ],
            'low_stock' => [
                'key' => 'low_stock',
                'name' => 'Low Stock Report',
                'description' => 'Products below their reorder point.',
                'category' => 'inventory',
                'route' => 'accounting.invsetup.lowstock',
                'permission' => 'view_inventory',
                'feature_flag' => ['inventory'],
            ],
            'stock_movement' => [
                'key' => 'stock_movement',
                'name' => 'Stock Movement Ledger',
                'description' => 'Per-item transaction history with running quantity — ties to Inventory Valuation.',
                'category' => 'inventory',
                'route' => 'accounting.reports.stock-movement',
                'permission' => 'view_inventory',
                'feature_flag' => ['inventory'],
            ],
            'stock_count_variance' => [
                'key' => 'stock_count_variance',
                'name' => 'Stock Count Variance',
                'description' => 'Counted vs. book quantity and resulting adjustments.',
                'category' => 'inventory',
                'route' => 'accounting.reports.stock-count-variance',
                'permission' => 'view_inventory',
                'feature_flag' => ['inventory'],
            ],
            'item_profitability' => [
                'key' => 'item_profitability',
                'name' => 'Item Profitability',
                'description' => 'Revenue minus FIFO-based COGS by item for a period.',
                'category' => 'inventory',
                'route' => 'accounting.reports.item-profitability',
                'permission' => 'view_reports',
                'feature_flag' => ['inventory'],
            ],

            // ── Banking ──
            'cash_position' => [
                'key' => 'cash_position',
                'name' => 'Cash Position',
                'description' => 'Current balances across all bank and petty cash accounts.',
                'category' => 'banking',
                'route' => 'accounting.cash-position.index',
                'permission' => 'view_reports',
                'feature_flag' => ['banking'],
            ],
            'bank_balances' => [
                'key' => 'bank_balances',
                'name' => 'Bank Account Balances',
                'description' => 'Summary of all bank account book balances.',
                'category' => 'banking',
                'route' => 'accounting.reports.bank-balances',
                'permission' => 'view_reports',
                'feature_flag' => ['banking'],
            ],
            'deposits_in_transit' => [
                'key' => 'deposits_in_transit',
                'name' => 'Deposits in Transit',
                'description' => 'Outstanding receipts in Undeposited Funds — reconciles to account balance.',
                'category' => 'banking',
                'route' => 'accounting.reports.deposits-in-transit',
                'permission' => 'view_reports',
                'feature_flag' => ['banking'],
            ],

            // ── Fixed Assets ──
            'fixed_asset_register' => [
                'key' => 'fixed_asset_register',
                'name' => 'Fixed Asset Register',
                'description' => 'Complete list of all fixed assets with acquisition and book values.',
                'category' => 'fixed_assets',
                'route' => 'accounting.fixed-assets.index',
                'permission' => 'view_reports',
                'feature_flag' => ['fixed_assets'],
            ],
            'depreciation_schedule' => [
                'key' => 'depreciation_schedule',
                'name' => 'Depreciation Schedule',
                'description' => 'Per-asset depreciation schedule with accumulated totals.',
                'category' => 'fixed_assets',
                'route' => 'accounting.fixed-assets.index',
                'permission' => 'view_reports',
                'feature_flag' => ['fixed_assets'],
            ],
            'depreciation_runs' => [
                'key' => 'depreciation_runs',
                'name' => 'Depreciation Run History',
                'description' => 'History of depreciation runs and posted amounts.',
                'category' => 'fixed_assets',
                'route' => 'accounting.depreciation.runs',
                'permission' => 'view_reports',
                'feature_flag' => ['fixed_assets'],
            ],
            'asset_revaluation' => [
                'key' => 'asset_revaluation',
                'name' => 'Asset Revaluation Report',
                'description' => 'Revaluation events and resulting surplus movements.',
                'category' => 'fixed_assets',
                'route' => 'accounting.reports.asset-revaluation',
                'permission' => 'view_reports',
                'feature_flag' => ['fixed_assets'],
            ],
            'asset_impairment' => [
                'key' => 'asset_impairment',
                'name' => 'Asset Impairment Report',
                'description' => 'Impairment and reversal events for a period.',
                'category' => 'fixed_assets',
                'route' => 'accounting.reports.asset-impairment',
                'permission' => 'view_reports',
                'feature_flag' => ['fixed_assets'],
            ],

            // ── Payroll ──
            'payroll_register' => [
                'key' => 'payroll_register',
                'name' => 'Payroll Register',
                'description' => 'Full detail per employee per run: gross, deductions, net.',
                'category' => 'payroll',
                'route' => 'accounting.reports.payroll-register',
                'permission' => 'view_reports',
                'feature_flag' => ['payroll'],
            ],
            'payroll_summary' => [
                'key' => 'payroll_summary',
                'name' => 'Payroll Summary',
                'description' => 'Totals by period: gross, PAYE, pension, net, employer cost.',
                'category' => 'payroll',
                'route' => 'accounting.reports.payroll-summary',
                'permission' => 'view_reports',
                'feature_flag' => ['payroll'],
            ],
            'employee_cost_by_branch' => [
                'key' => 'employee_cost_by_branch',
                'name' => 'Employee Cost by Branch',
                'description' => 'Payroll expense grouped by branch/cost center.',
                'category' => 'payroll',
                'route' => 'accounting.reports.employee-cost-by-branch',
                'permission' => 'view_reports',
                'feature_flag' => ['payroll'],
            ],

            // ── Budgeting ──
            'budget_vs_actual' => [
                'key' => 'budget_vs_actual',
                'name' => 'Budget vs Actual',
                'description' => 'Compare budgeted amounts against GL actuals by account and period.',
                'category' => 'budgeting',
                'route' => 'accounting.budgets.vsactual',
                'permission' => 'view_reports',
                'feature_flag' => ['budgets'],
            ],
            'budget_forecast' => [
                'key' => 'budget_forecast',
                'name' => 'Budget Forecast',
                'description' => 'Project year-end budget performance from current period trends.',
                'category' => 'budgeting',
                'route' => 'accounting.budgets.forecast',
                'permission' => 'view_reports',
                'feature_flag' => ['budgets'],
            ],
            'budget_adjustments' => [
                'key' => 'budget_adjustments',
                'name' => 'Budget Adjustments',
                'description' => 'History of budget increases, reductions, and transfers.',
                'category' => 'budgeting',
                'route' => 'accounting.budgets.adjustments',
                'permission' => 'view_reports',
                'feature_flag' => ['budgets'],
            ],

            // ── Compliance & Audit ──
            'audit_log' => [
                'key' => 'audit_log',
                'name' => 'Audit Log',
                'description' => 'System-wide audit trail of user actions.',
                'category' => 'compliance',
                'route' => 'admin.audit-log.index',
                'permission' => null,
                'feature_flag' => null,
            ],
            'period_lock_status' => [
                'key' => 'period_lock_status',
                'name' => 'Period Lock Status',
                'description' => 'Status of every fiscal year/period (open, closed, locked).',
                'category' => 'compliance',
                'route' => 'accounting.reports.period-lock-status',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'chart_of_accounts' => [
                'key' => 'chart_of_accounts',
                'name' => 'Chart of Accounts',
                'description' => 'Full listing of all accounts grouped by type with current and opening balances.',
                'category' => 'financial_statements',
                'route' => 'accounting.reports.chart-of-accounts',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'customer_statement' => [
                'key' => 'customer_statement',
                'name' => 'Customer Statement',
                'description' => 'Invoices, payments, and credit notes for a specific customer with running balance.',
                'category' => 'sales',
                'route' => 'accounting.reports.customer-statement',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'vendor_statement' => [
                'key' => 'vendor_statement',
                'name' => 'Vendor Statement',
                'description' => 'Bills, payments, and vendor credits for a specific vendor with running balance.',
                'category' => 'purchasing',
                'route' => 'accounting.reports.vendor-statement',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'unbilled_deliveries' => [
                'key' => 'unbilled_deliveries',
                'name' => 'Unbilled Deliveries',
                'description' => 'Quotation line items not yet converted to invoices or receipts.',
                'category' => 'sales',
                'route' => 'accounting.reports.unbilled-deliveries',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'cheque_register' => [
                'key' => 'cheque_register',
                'name' => 'Cheque Register',
                'description' => 'All cheque transactions with payee, amount, and reconciliation status.',
                'category' => 'banking',
                'route' => 'accounting.reports.cheque-register',
                'permission' => 'view_reports',
                'feature_flag' => ['banking'],
            ],
            'asset_disposal_report' => [
                'key' => 'asset_disposal_report',
                'name' => 'Asset Disposal Report',
                'description' => 'All asset disposals with proceeds, gain/loss, and disposal method.',
                'category' => 'fixed_assets',
                'route' => 'accounting.reports.asset-disposal-report',
                'permission' => 'view_reports',
                'feature_flag' => ['fixed_assets'],
            ],
            'tax_depreciation_schedule' => [
                'key' => 'tax_depreciation_schedule',
                'name' => 'Tax Depreciation Schedule',
                'description' => 'Per-asset tax depreciation schedule with accumulated totals and net book value.',
                'category' => 'fixed_assets',
                'route' => 'accounting.reports.tax-depreciation-schedule',
                'permission' => 'view_reports',
                'feature_flag' => ['fixed_assets'],
            ],
            'paye_remittance_report' => [
                'key' => 'paye_remittance_report',
                'name' => 'PAYE Remittance Report',
                'description' => 'PAYE tax amounts per payroll run for remittance to tax authorities.',
                'category' => 'payroll',
                'route' => 'accounting.reports.paye-remittance-report',
                'permission' => 'view_reports',
                'feature_flag' => ['payroll'],
            ],
            'pension_remittance_report' => [
                'key' => 'pension_remittance_report',
                'name' => 'Pension Remittance Report',
                'description' => 'EE and ER pension contributions per run for remittance to pension fund.',
                'category' => 'payroll',
                'route' => 'accounting.reports.pension-remittance-report',
                'permission' => 'view_reports',
                'feature_flag' => ['payroll'],
            ],
            'payslip_report' => [
                'key' => 'payslip_report',
                'name' => 'Payslip Report',
                'description' => 'Detailed payslip data per employee per payroll run.',
                'category' => 'payroll',
                'route' => 'accounting.reports.payslip-report',
                'permission' => 'view_reports',
                'feature_flag' => ['payroll'],
            ],
            'consolidated_balance_sheet' => [
                'key' => 'consolidated_balance_sheet',
                'name' => 'Consolidated Balance Sheet',
                'description' => 'Combined balance sheet across all companies with prior-period comparison.',
                'category' => 'financial_statements',
                'route' => 'accounting.reports.consolidated-balance-sheet',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'consolidated_income_statement' => [
                'key' => 'consolidated_income_statement',
                'name' => 'Consolidated Income Statement',
                'description' => 'Combined income statement across all companies with prior-period comparison.',
                'category' => 'financial_statements',
                'route' => 'accounting.reports.consolidated-income-statement',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'pending_approvals_aging' => [
                'key' => 'pending_approvals_aging',
                'name' => 'Pending Approvals Aging',
                'description' => 'Outstanding bills, purchase requisitions, and budgets awaiting approval with aging.',
                'category' => 'compliance',
                'route' => 'accounting.reports.pending-approvals-aging',
                'permission' => 'view_reports',
                'feature_flag' => null,
            ],
            'eis_submission_status' => [
                'key' => 'eis_submission_status',
                'name' => 'EIS Submission Status',
                'description' => 'E-Invoicing submission status and error monitoring for all POS transactions.',
                'category' => 'compliance',
                'route' => 'accounting.reports.eis-submission-status',
                'permission' => 'view_reports',
                'feature_flag' => ['pos'],
            ],
            'assembly_build_history' => [
                'key' => 'assembly_build_history',
                'name' => 'Assembly Build History',
                'description' => 'History of assembly builds and unbuilds with component costs.',
                'category' => 'inventory',
                'route' => 'accounting.reports.assembly-build-history',
                'permission' => 'view_reports',
                'feature_flag' => ['inventory'],
            ],

            // ── Analytics ──
            'financial_ratios' => [
                'key' => 'financial_ratios',
                'name' => 'Financial Ratios',
                'description' => 'Key financial ratios computed from statements.',
                'category' => 'analytics',
                'route' => 'analytics.financial-ratios',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'revenue_expense_trends' => [
                'key' => 'revenue_expense_trends',
                'name' => 'Revenue & Expense Trends',
                'description' => 'Trend analysis of revenue and expenses over time.',
                'category' => 'analytics',
                'route' => 'analytics.revenue-expense-trends',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'sales_analytics' => [
                'key' => 'sales_analytics',
                'name' => 'Sales Analytics',
                'description' => 'Sales performance analytics and trends.',
                'category' => 'analytics',
                'route' => 'analytics.sales',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'purchasing_analytics' => [
                'key' => 'purchasing_analytics',
                'name' => 'Purchasing Analytics',
                'description' => 'Purchasing performance analytics.',
                'category' => 'analytics',
                'route' => 'analytics.purchasing',
                'permission' => 'view_reports',
                'feature_flag' => ['analytics', 'purchasing'],
            ],
            'inventory_analytics' => [
                'key' => 'inventory_analytics',
                'name' => 'Inventory Analytics',
                'description' => 'Inventory performance analytics.',
                'category' => 'analytics',
                'route' => 'analytics.inventory',
                'permission' => 'view_inventory',
                'feature_flag' => ['analytics', 'inventory'],
            ],
            'profitability_analytics' => [
                'key' => 'profitability_analytics',
                'name' => 'Profitability Analytics',
                'description' => 'Profitability analysis across dimensions.',
                'category' => 'analytics',
                'route' => 'analytics.profitability',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],
            'cash_flow_trend' => [
                'key' => 'cash_flow_trend',
                'name' => 'Cash Flow Trend & Projection',
                'description' => 'Cash flow trend analysis and forward projection.',
                'category' => 'analytics',
                'route' => 'analytics.cash-flow-trend',
                'permission' => 'view_reports',
                'feature_flag' => 'analytics',
            ],

            // ── Business Intelligence ──
            'true_total_cost' => [
                'key' => 'true_total_cost',
                'name' => 'True Total Cost per Branch',
                'description' => 'Fully loaded cost analysis per branch.',
                'category' => 'bi',
                'route' => 'bi.true-total-cost',
                'permission' => 'view_reports',
                'feature_flag' => 'bi',
            ],
            'customer_lifetime_value' => [
                'key' => 'customer_lifetime_value',
                'name' => 'Customer Lifetime Value',
                'description' => 'CLV analysis per customer.',
                'category' => 'bi',
                'route' => 'bi.customer-lifetime-value',
                'permission' => 'view_reports',
                'feature_flag' => 'bi',
            ],
            'employee_productivity' => [
                'key' => 'employee_productivity',
                'name' => 'Employee Productivity',
                'description' => 'Revenue and cost per employee metrics.',
                'category' => 'bi',
                'route' => 'bi.employee-productivity',
                'permission' => 'view_reports',
                'feature_flag' => 'bi',
            ],
            'branch_profitability' => [
                'key' => 'branch_profitability',
                'name' => 'Branch Profitability',
                'description' => 'Profitability comparison across branches.',
                'category' => 'bi',
                'route' => 'bi.branch-profitability',
                'permission' => 'view_reports',
                'feature_flag' => 'bi',
            ],

            // ── Point of Sale ──
            'pos_x_report' => [
                'key' => 'pos_x_report',
                'name' => 'X-Report',
                'description' => 'Mid-shift summary of sales and payments.',
                'category' => 'pos',
                'route' => 'pos.reports.x-report',
                'permission' => 'view_reports',
                'feature_flag' => 'pos',
            ],
            'pos_z_report' => [
                'key' => 'pos_z_report',
                'name' => 'Z-Report',
                'description' => 'End-of-day sales and payment summary.',
                'category' => 'pos',
                'route' => 'pos.reports.z-report',
                'permission' => 'view_reports',
                'feature_flag' => 'pos',
            ],
            'pos_sales_by_terminal' => [
                'key' => 'pos_sales_by_terminal',
                'name' => 'Sales by Terminal',
                'description' => 'Sales performance grouped by POS terminal.',
                'category' => 'pos',
                'route' => 'pos.reports.sales-by-terminal',
                'permission' => 'view_reports',
                'feature_flag' => 'pos',
            ],
            'pos_sales_by_cashier' => [
                'key' => 'pos_sales_by_cashier',
                'name' => 'Sales by Cashier',
                'description' => 'Sales performance grouped by cashier.',
                'category' => 'pos',
                'route' => 'pos.reports.sales-by-cashier',
                'permission' => 'view_reports',
                'feature_flag' => 'pos',
            ],
        ];

        return self::$reports;
    }

    public static function getCategories(): array
    {
        return self::CATEGORIES;
    }

    public static function getCategoryLabel(string $key): ?string
    {
        return self::CATEGORIES[$key] ?? null;
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function getByCategory(string $category): array
    {
        return array_filter(self::all(), fn ($r) => $r['category'] === $category);
    }

    public static function getAnalyticsReports(): array
    {
        return self::getByCategory('analytics');
    }

    public static function isAccessible(string $key, $user, int $companyId): bool
    {
        $report = self::get($key);
        if (!$report) return false;

        $flags = (array) ($report['feature_flag'] ?? []);
        foreach ($flags as $flag) {
            if (!\App\Services\FeatureManagement::isEnabled($companyId, $flag)) {
                return false;
            }
        }

        if ($report['permission']) {
            $currentTeamId = getPermissionsTeamId();
            setPermissionsTeamId($companyId);
            $result = $user->hasAnyRole([$report['permission'], 'system_admin', 'company_admin']);
            setPermissionsTeamId($currentTeamId);
            return $result;
        }

        return true;
    }

    public static function isRouteDefined(string $routeName): bool
    {
        return Route::has($routeName);
    }

    public static function getAccessible($user, int $companyId, ?string $category = null): array
    {
        $reports = $category ? self::getByCategory($category) : self::all();
        return array_filter($reports, function ($r) use ($user, $companyId) {
            return self::isAccessible($r['key'], $user, $companyId) && self::isRouteDefined($r['route']);
        });
    }

    public static function getAccessibleGrouped($user, int $companyId): array
    {
        $accessible = self::getAccessible($user, $companyId);
        $grouped = [];

        foreach (self::CATEGORIES as $catKey => $catLabel) {
            $catReports = array_filter($accessible, fn ($r) => $r['category'] === $catKey);
            if (!empty($catReports)) {
                $grouped[$catKey] = [
                    'label' => $catLabel,
                    'reports' => $catReports,
                ];
            }
        }

        return $grouped;
    }
}
