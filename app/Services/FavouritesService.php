<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFavourite;
use App\Models\UserPreference;
use Illuminate\Support\Collection;

/**
 * Favourites registry + persistence.
 *
 * Every page that can be favourited is declared here (or passes explicit
 * metadata via <x-favourite-toggle> for record detail pages). The registry
 * maps a route name to a stable `page_key`, a display label, an icon and
 * the URL to navigate to when the tile is clicked.
 */
class FavouritesService
{
    public const MAX_FAVOURITES = 20;

    /**
     * Route name → [key, label, icon]. URLs are resolved via route().
     * `key` is the stable identity used in the user_favourites.page_key
     * column and in the front-end store; record pages use keys like
     * `vendor:123` built at render time.
     */
    public const PAGES = [
        'dashboard' => ['dashboard', 'Dashboard', 'dashboard'],
        'todo.index' => ['my-tasks', 'My Tasks', 'list-check'],
        'accounting.customers.index' => ['customers', 'Customers', 'users'],
        'accounting.vendors.index' => ['vendors', 'Vendors', 'truck'],
        'accounting.invoices.index' => ['invoices', 'Invoices', 'invoice'],
        'accounting.bills.index' => ['bills', 'Bills', 'file-text'],
        'accounting.sales-receipts.index' => ['sales-receipts', 'Sales Receipts', 'receipt'],
        'accounting.quotations.index' => ['quotations', 'Quotations', 'file-text'],
        'accounting.credit-notes.index' => ['credit-notes', 'Credit Notes', 'file-minus'],
        'accounting.vendor-credits.index' => ['vendor-credits', 'Vendor Credits', 'file-minus'],
        'accounting.purchase-orders.index' => ['purchase-orders', 'Purchase Orders', 'shopping-cart'],
        'accounting.purchase-requisitions.index' => ['purchase-requisitions', 'Purchase Requisitions', 'clipboard'],
        'accounting.goods-received-notes.index' => ['goods-received-notes', 'Goods Received Notes', 'package-check'],
        'accounting.expenses.index' => ['expenses', 'Expenses', 'wallet'],
        'accounting.expenses.claims.index' => ['expense-claims', 'Expense Claims', 'clipboard'],
        'accounting.expenses.recurring.index' => ['expense-recurring', 'Recurring Expenses', 'repeat'],
        'accounting.expenses.categories.index' => ['expense-categories', 'Expense Categories', 'folder'],
        'accounting.vendors.dashboard' => ['vendor-centre', 'Vendor Centre', 'layout-grid'],
        'accounting.inventory.dashboard' => ['inventory-centre', 'Inventory Centre', 'package'],
        'accounting.inventory.items' => ['inventory-items', 'Inventory Items', 'box'],
        'accounting.invsetup.categories' => ['item-categories', 'Item Categories', 'folder'],
        'accounting.invsetup.assemblies' => ['assemblies', 'Assemblies', 'layers'],
        'accounting.invsetup.transfers' => ['stock-transfers', 'Stock Transfers', 'arrow-right-left'],
        'accounting.invsetup.adjustments' => ['stock-adjustments', 'Stock Adjustments', 'sliders'],
        'accounting.invsetup.stockcount' => ['stock-count', 'Stock Count', 'clipboard-check'],
        'accounting.invsetup.uom' => ['uom-conversions', 'UOM & Landed Costs', 'ruler'],
        'accounting.invsetup.valuation' => ['valuation', 'Valuation', 'scale'],
        'accounting.invsetup.lowstock' => ['low-stock', 'Low Stock', 'alert-triangle'],
        'accounting.banking.dashboard' => ['banking-centre', 'Banking Centre', 'bank'],
        'accounting.banking.transfers' => ['bank-transfer', 'Transfer Funds', 'arrow-left-right'],
        'accounting.banking.deposits' => ['deposits', 'Deposits', 'arrow-down-circle'],
        'accounting.banking.cheques' => ['cheques', 'Cheques', 'credit-card'],
        'accounting.banking.petty' => ['petty-cash', 'Petty Cash', 'cash'],
        'accounting.budgets.dashboard' => ['budget-dashboard', 'Budget Dashboard', 'piggy-bank'],
        'accounting.budgets.index' => ['budget-list', 'All Budgets', 'list'],
        'accounting.budgets.reports' => ['budget-reports', 'Budget Reports', 'bar-chart'],
        'accounting.payroll.dashboard' => ['payroll-centre', 'Payroll Centre', 'users'],
        'accounting.payroll.employees.index' => ['employees', 'Employees', 'users'],
        'accounting.payroll.runs.index' => ['payroll-runs', 'Payroll Runs', 'list-check'],
        'accounting.payroll.payslips.index' => ['payslips', 'Payslips', 'file-text'],
        'accounting.payroll.statutory.index' => ['payroll-statutory', 'Statutory', 'shield'],
        'accounting.payroll.people.index' => ['payroll-people', 'People', 'users'],
        'accounting.payroll.reports.index' => ['payroll-reports', 'Payroll Reports', 'bar-chart'],
        'accounting.payroll.settings.index' => ['payroll-settings', 'Payroll Settings', 'settings'],
        'accounting.accounts.index' => ['chart-of-accounts', 'Chart of Accounts', 'book'],
        'accounting.journal-entries.index' => ['journal-entries', 'Journal Entries', 'book-open'],
        'accounting.general-ledger.index' => ['general-ledger', 'General Ledger', 'scroll'],
        'accounting.trial-balance.index' => ['trial-balance', 'Trial Balance', 'scale'],
        'accounting.fiscal-years.index' => ['fiscal-years', 'Fiscal Years', 'calendar'],
        'accounting.periods.index' => ['accounting-periods', 'Accounting Periods', 'calendar-clock'],
        'accounting.recurring-journals.index' => ['recurring-journals', 'Recurring Journals', 'repeat'],
        'accounting.cost-centers.index' => ['cost-centers', 'Cost Centers', 'building'],
        'accounting.exchange-rates.index' => ['exchange-rates', 'Exchange Rates', 'globe'],
        'accounting.account-classification.index' => ['account-classification', 'Account Classification', 'tags'],
        'accounting.asset-categories.index' => ['asset-categories', 'Asset Categories', 'folder'],
        'accounting.fixed-assets.index' => ['fixed-assets', 'Asset Register', 'box'],
        'accounting.depreciation.runs' => ['depreciation', 'Depreciation Runs', 'trend-down'],
        'accounting.asset-usage.index' => ['asset-usage', 'Asset Usage (UOP)', 'activity'],
        'accounting.asset-disposals.index' => ['asset-disposals', 'Asset Disposals', 'trash'],
        'accounting.asset-transfers.index' => ['asset-transfers', 'Asset Transfers', 'swap'],
        'accounting.asset-impairments.index' => ['asset-impairments', 'Asset Impairments', 'trend-down'],
        'accounting.asset-revaluations.index' => ['asset-revaluations', 'Asset Revaluations', 'trend-up'],
        'accounting.report-center.index' => ['report-center', 'Report Center', 'layout-grid'],
        'accounting.income-statement.index' => ['income-statement', 'Income Statement', 'trend-up'],
        'accounting.balance-sheet.index' => ['balance-sheet', 'Balance Sheet', 'scale'],
        'accounting.cash-flow.index' => ['cash-flow', 'Cash Flow', 'repeat'],
        'accounting.aging.ar-summary' => ['ar-aging', 'A/R Aging', 'clock'],
        'accounting.aging.ap-summary' => ['ap-aging', 'A/P Aging', 'clock'],
        'analytics.financial-ratios' => ['financial-ratios', 'Financial Ratios', 'chart-line'],
        'analytics.revenue-expense-trends' => ['revenue-expense-trends', 'Revenue vs Expense', 'chart-line'],
        'analytics.sales' => ['analytics-sales', 'Sales Analytics', 'chart-bar'],
        'analytics.purchasing' => ['analytics-purchasing', 'Purchasing Analytics', 'chart-bar'],
        'analytics.inventory' => ['analytics-inventory', 'Inventory Analytics', 'chart-bar'],
        'analytics.profitability' => ['analytics-profitability', 'Profitability', 'chart-line'],
        'analytics.cash-flow-trend' => ['analytics-cash-flow', 'Cash Flow Trend', 'trend-up'],
        'bi.true-total-cost' => ['true-total-cost', 'True Total Cost', 'cpu'],
        'bi.customer-lifetime-value' => ['customer-ltv', 'Customer LTV', 'chart-line'],
        'bi.employee-productivity' => ['employee-productivity', 'Employee Productivity', 'users'],
        'bi.branch-profitability' => ['branch-profitability', 'Branch Profitability', 'building'],
        'pos.terminals.index' => ['pos-terminals', 'POS Terminals', 'monitor'],
        'pos.payment-methods.index' => ['pos-payment-methods', 'POS Payment Methods', 'credit-card'],
        'pos.till-sessions.index' => ['till-sessions', 'Till Sessions', 'lock'],
        'pos.sales.checkout' => ['pos-checkout', 'POS Checkout', 'shopping-cart'],
        'pos.returns.index' => ['pos-returns', 'POS Returns', 'refresh'],
        'pos.settlements.index' => ['pos-settlements', 'POS Settlements', 'coins'],
        'pos.reports.x-report' => ['pos-x-report', 'X-Report', 'file-text'],
        'pos.reports.z-report' => ['pos-z-report', 'Z-Report', 'file-text'],
        'pos.reports.sales-by-terminal' => ['pos-sales-by-terminal', 'Sales by Terminal', 'monitor'],
        'pos.reports.sales-by-cashier' => ['pos-sales-by-cashier', 'Sales by Cashier', 'user'],
        'pos.eis.terminals' => ['eis-terminals', 'EIS Terminals', 'monitor'],
        'pos.eis.submissions' => ['eis-submissions', 'EIS Submissions', 'file-text'],
        'system-settings.index' => ['system-settings', 'System Settings', 'settings'],
        'system-settings.features' => ['features', 'Features', 'toggle'],
        'system-settings.audit-log' => ['settings-audit-log', 'Settings Audit Log', 'scroll'],
        'admin.numbering-sequences.index' => ['numbering-sequences', 'Numbering Sequences', 'hash'],
        'admin.security.index' => ['security', 'Security', 'shield'],
        'admin.notifications.index' => ['notifications', 'Notifications', 'bell'],
        'admin.backups.index' => ['backups', 'Backups', 'database'],
        'admin.system-health.index' => ['system-health', 'System Health', 'heart-pulse'],
        'admin.audit-log.index' => ['audit-log', 'Audit Log', 'scroll'],
        'admin.users.index' => ['users-roles', 'Users & Roles', 'users'],
        'admin.permissions.index' => ['permissions', 'Permission Manager', 'shield-check'],
        'admin.setup-wizard.index' => ['setup-wizard', 'Setup Wizard', 'wand'],
        'companies.index' => ['companies', 'Companies', 'building'],
        'branches.index' => ['branches', 'Branches', 'git-branch'],
    ];

    /**
     * Record show route → [key prefix, eyebrow, icon]. Stars on detail pages
     * are derived automatically: the page_key becomes `{prefix}:{id}` where
     * the id comes from the first route parameter (model or scalar), the
     * label falls back to the page header text, and the URL to the current
     * request URL.
     */
    public const RECORD_PAGES = [
        'accounting.accounts.show' => ['account', 'Account', 'book'],
        'accounting.journal-entries.show' => ['journal-entry', 'Journal Entry', 'book-open'],
        'accounting.fiscal-years.show' => ['fiscal-year', 'Fiscal Year', 'calendar'],
        'accounting.recurring-journals.show' => ['recurring-journal', 'Recurring Journal', 'repeat'],
        'accounting.customers.show' => ['customer', 'Customer', 'users'],
        'accounting.vendors.show' => ['vendor', 'Vendor', 'truck'],
        'accounting.inventory.items.show' => ['inventory-item', 'Inventory Item', 'box'],
        'accounting.invoices.show' => ['invoice', 'Invoice', 'invoice'],
        'accounting.credit-notes.show' => ['credit-note', 'Credit Note', 'file-minus'],
        'accounting.quotations.show' => ['quotation', 'Quotation', 'file-text'],
        'accounting.sales-receipts.show' => ['sales-receipt', 'Sales Receipt', 'receipt'],
        'accounting.customer-payments.show' => ['customer-payment', 'Customer Payment', 'arrow-down-circle'],
        'accounting.bills.show' => ['bill', 'Bill', 'file-text'],
        'accounting.vendor-credits.show' => ['vendor-credit', 'Vendor Credit', 'file-minus'],
        'accounting.vendor-payments.show' => ['vendor-payment', 'Vendor Payment', 'wallet'],
        'accounting.expenses.show' => ['expense', 'Expense', 'wallet'],
        'accounting.expenses.claims.show' => ['expense-claim', 'Expense Claim', 'clipboard'],
        'accounting.purchase-requisitions.show' => ['purchase-requisition', 'Purchase Requisition', 'clipboard'],
        'accounting.purchase-orders.show' => ['purchase-order', 'Purchase Order', 'shopping-cart'],
        'accounting.goods-received-notes.show' => ['goods-received-note', 'Goods Received Note', 'package-check'],
        'accounting.banking.cheques.show' => ['cheque', 'Cheque', 'credit-card'],
        'accounting.banking.petty.show' => ['petty-cash-fund', 'Petty Cash Fund', 'cash'],
        'accounting.budgets.show' => ['budget', 'Budget', 'piggy-bank'],
        'accounting.payroll.payslips.show' => ['payslip', 'Payslip', 'file-text'],
        'accounting.landed-costs.show' => ['landed-cost', 'Landed Cost', 'anchor'],
        'accounting.asset-categories.show' => ['asset-category', 'Asset Category', 'folder'],
        'accounting.fixed-assets.show' => ['asset', 'Fixed Asset', 'box'],
        'admin.numbering-sequences.show' => ['numbering-sequence', 'Numbering Sequence', 'hash'],
        'pos.till-sessions.show' => ['till-session', 'Till Session', 'lock'],
        'pos.settlements.show' => ['settlement', 'Settlement', 'coins'],
        'pos.returns.show' => ['pos-return', 'POS Return', 'refresh'],
    ];

    /**
     * Icon name → SVG path data (lucide-style, 24x24 stroke). Rendered as
     * `<svg fill="none" stroke="currentColor"><path d="..."/></svg>`.
     */
    public const ICONS = [
        'dashboard' => 'M12 3a9 9 0 109 9 9 9 0 00-9-9zM12 12l3.5-3.5M12 8V3',
        'list-check' => 'M3 17l2 2 4-4M3 7l2 2 4-4M13 6h8M13 12h8M13 18h8',
        'users' => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M9 11a4 4 0 100-8 4 4 0 000 8z',
        'truck' => 'M1 3h15v13H1zM16 8h4l3 3v5h-7V8zM5.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM18.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'box' => 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12',
        'user' => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z',
        'invoice' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8',
        'file-text' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8',
        'receipt' => 'M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1zM16 8h-6M16 12H8M16 16H8',
        'file-minus' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M9 15h6',
        'shopping-cart' => 'M9 22a1 1 0 100-2 1 1 0 000 2zM20 22a1 1 0 100-2 1 1 0 000 2zM1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6',
        'clipboard' => 'M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2M15 2H9a1 1 0 00-1 1v2a1 1 0 001 1h6a1 1 0 001-1V3a1 1 0 00-1-1zM9 12h6M9 16h6',
        'package-check' => 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12M9 10l2 2 4-4',
        'wallet' => 'M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 000 4h4v-4z',
        'layout-grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
        'package' => 'M16.5 9.4L7.55 4.24M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12',
        'folder' => 'M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z',
        'layers' => 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
        'sliders' => 'M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6',
        'swap' => 'M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4',
        'clipboard-check' => 'M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2M15 2H9a1 1 0 00-1 1v2a1 1 0 001 1h6a1 1 0 001-1V3a1 1 0 00-1-1zM9 14l2 2 4-4',
        'ruler' => 'M16.5 3.5l4 4L7.5 20.5l-4-4zM12 8l4 4M8 12l4 4',
        'anchor' => 'M12 22V8M5 12H2a10 10 0 0020 0h-3M12 8a3 3 0 100-6 3 3 0 000 6z',
        'scale' => 'M12 3v18M3 7h18M6 7l-3 5 3 5m12-10l3 5-3 5M7 21h10',
        'alert-triangle' => 'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01',
        'bank' => 'M3 21h18M3 10h18M5 10v11M19 10v11M10 10v11M14 10v11M3 10l9-6 9 6M3 21h18',
        'arrow-left-right' => 'M8 3L4 7l4 4M4 7h16M16 21l4-4-4-4M20 17H4',
        'arrow-down-circle' => 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 8v8M8 12l4 4 4-4',
        'credit-card' => 'M1 10h22M1 6a2 2 0 012-2h18a2 2 0 012 2v12a2 2 0 01-2 2H3a2 2 0 01-2-2zM1 10h22',
        'cash' => 'M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6',
        'book' => 'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 016.5 22H20V2H6.5A2.5 2.5 0 004 4.5z',
        'book-open' => 'M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2zM22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z',
        'scroll' => 'M19 17V5a2 2 0 00-2-2H4M19 17a2 2 0 002-2M19 17v1a2 2 0 01-2 2H8M4 4h11v9H4V4zM4 21a2 2 0 01-2-2V3',
        'calendar' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z',
        'calendar-clock' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zM9 16l3 3 5-5',
        'repeat' => 'M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3',
        'building' => 'M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18M6 22H4m2 0h16M2 22h20M8 6h8M8 10h8M8 14h8M8 18h8',
        'globe' => 'M12 22a10 10 0 100-20 10 10 0 000 20zM2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z',
        'tags' => 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.83zM7 7h.01',
        'target' => 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 18a6 6 0 100-12 6 6 0 000 12zM12 14a2 2 0 100-4 2 2 0 000 4z',
        'trend-down' => 'M23 18l-9.5-9.5-5 5L1 6M17 18h6v-6',
        'trend-up' => 'M23 6l-9.5 9.5-5-5L1 18M17 6h6v6',
        'activity' => 'M22 12h-4l-3 9L9 3l-3 9H2',
        'trash' => 'M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6',
        'banknote' => 'M2 6h20v12H2zM2 10h20M2 14h20M2 18h20M9 6c0 2-1 2-1 4M15 18c0-2 1-2 1-4',
        'percent' => 'M19 5L5 19M6.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM17.5 20a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'shield' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
        'chart-line' => 'M3 3v18h18M19 9l-5 5-4-4-5 5',
        'chart-bar' => 'M18 20V10M12 20V4M6 20v-6',
        'clock' => 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 6v6l4 2',
        'cpu' => 'M9 9h6v6H9zM9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 15h3M1 9h3M1 15h3M5 5h14v14H5z',
        'monitor' => 'M2 4h20v14H2zM8 22h8M12 18v4',
        'lock' => 'M19 11H5a2 2 0 00-2 2v7a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-2-2zM7 11V7a5 5 0 0110 0v4',
        'refresh' => 'M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8M3 3v5h5',
        'coins' => 'M8 21h8M12 17v4M17 4a4 4 0 00-10 0c0 4 10 4 10 8a4 4 0 01-8 0M8 21h8M12 17v4',
        'settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'toggle' => 'M2 12a4 4 0 014-4h12a4 4 0 010 8H6a4 4 0 01-4-4zM6 8a4 4 0 100 8',
        'hash' => 'M4 9h16M4 15h16M10 3L8 21M16 3l-2 18',
        'bell' => 'M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0',
        'database' => 'M12 3c3.9 0 7 1.8 7 4s-3.1 4-7 4-7-1.8-7-4 3.1-4 7-4zM5 7v6c0 2.2 3.1 4 7 4s7-1.8 7-4V7M5 13v6c0 2.2 3.1 4 7 4s7-1.8 7-4v-6',
        'heart-pulse' => 'M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 000-7.8zM3.2 12h6l2-3 2 6 2-3h6',
        'shield-check' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9 11l2 2 4-4',
        'wand' => 'M15 4V2M15 16v-2M8 9h2M20 9h2M17.8 11.8L19 13M15 9h.01M17.8 6.2L19 5M3 21l9-9M12.2 6.2L11 5',
        'git-branch' => 'M6 3v12M18 9a4 4 0 100-8 4 4 0 000 8zM6 21a4 4 0 100-8 4 4 0 000 8zM18 9c-1.5 0-3 .8-4 2s-2.5 2-4 2',
        'star' => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z',
        'pin' => 'M12 17v5M9 2h6l1 7 3 3v2H5v-2l3-3z',
        'chevron-left' => 'M15 18l-6-6 6-6',
        'x' => 'M18 6L6 18M6 6l12 12',
        'plus' => 'M12 5v14M5 12h14',
    ];

    /**
     * Resolve page metadata for a route name, or null when the route is not
     * registrable. URL is resolved from the route at render time.
     */
    public static function metaForRoute(string $routeName): ?array
    {
        if (!array_key_exists($routeName, self::PAGES)) {
            return null;
        }

        [$key, $label, $icon] = self::PAGES[$routeName];

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'url' => route($routeName),
        ];
    }

    /**
     * Resolve page metadata for a record detail page. Reads the first route
     * parameter (the route-model-bound model instance, or a scalar id) and
     * builds a `{prefix}:{id}` page key. The label falls back to the page
     * header text passed in, and the URL to the current request URL.
     */
    public static function metaForRecord(string $routeName, string $header = ''): ?array
    {
        if (!array_key_exists($routeName, self::RECORD_PAGES)) {
            return null;
        }

        [$prefix, $eyebrow, $icon] = self::RECORD_PAGES[$routeName];

        $parameters = request()->route()?->parameters() ?? [];
        $first = reset($parameters);
        $id = $first instanceof \Illuminate\Database\Eloquent\Model ? $first->getKey() : $first;

        if ($id === null) {
            return null;
        }

        return [
            'key' => $prefix . ':' . $id,
            'label' => $header ?: ($eyebrow . ' ' . $id),
            'icon' => $icon,
            'eyebrow' => $eyebrow,
            'url' => request()->url(),
        ];
    }

    public static function svg(string $icon): string
    {
        $d = self::ICONS[$icon] ?? self::ICONS['star'];

        return '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">'
            .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="'.e($d).'"/>'
            .'</svg>';
    }

    public function listForUser(User $user): Collection
    {
        return $user->favourites()->get()->map(fn (UserFavourite $f) => [
            'page_key' => $f->page_key,
            'label' => $f->label,
            'icon' => $f->icon,
            'url' => $f->url,
        ]);
    }

    public function isPinned(User $user): bool
    {
        return $user->preference?->sidebar_pinned ?? false;
    }

    public function setPinned(User $user, bool $pinned): void
    {
        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            ['sidebar_pinned' => $pinned],
        );
    }

    public function add(User $user, string $pageKey, string $label, string $icon, string $url): ?UserFavourite
    {
        if ($user->favourites()->count() >= self::MAX_FAVOURITES) {
            return null;
        }

        return UserFavourite::updateOrCreate(
            ['user_id' => $user->id, 'page_key' => $pageKey],
            ['label' => $label, 'icon' => $icon, 'url' => $url, 'sort_order' => $user->favourites()->count()],
        );
    }

    public function remove(User $user, string $pageKey): void
    {
        $user->favourites()->where('page_key', $pageKey)->delete();
    }

    public function reorder(User $user, array $keys): void
    {
        $rows = $user->favourites()->get()->keyBy('page_key');

        foreach (array_values($keys) as $index => $key) {
            if ($row = $rows->get($key)) {
                $row->update(['sort_order' => $index]);
            }
        }
    }
}
