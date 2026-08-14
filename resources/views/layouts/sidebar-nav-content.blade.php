@php
    $dashboardActive = request()->routeIs('dashboard');
    $currentRoute = request()->route();
    $routeName = $currentRoute ? $currentRoute->getName() : '';
    $companyId = session('current_company_id') ?? 0;
    $sectionMap = [
        'sales-purchases' => ['accounting.customers', 'accounting.quotations', 'accounting.invoices', 'accounting.sales-receipts', 'accounting.credit-notes', 'accounting.vendors', 'accounting.purchase-orders', 'accounting.goods-received-notes', 'accounting.bills', 'accounting.expenses', 'accounting.vendor-centre', 'accounting.products'],
        'inventory'       => ['accounting.inventory-items', 'accounting.stock-adjustments', 'accounting.stock-transfers', 'accounting.stock-counts', 'accounting.inventory-valuation'],
        'banking'         => ['accounting.bank-accounts', 'accounting.bank-reconciliation', 'accounting.deposits', 'accounting.cheques', 'accounting.petty-cash', 'accounting.cash-position'],
        'accounting'      => ['accounting.accounts', 'accounting.journal-entries', 'accounting.general-ledger', 'accounting.trial-balance', 'accounting.budgets'],
        'fixed-assets'    => ['accounting.fixed-assets', 'accounting.asset-depreciation', 'accounting.depreciation'],
        'payroll'         => ['accounting.employees', 'accounting.payroll-runs'],
        'reports'         => ['accounting.report', 'accounting.income-statement', 'accounting.balance-sheet', 'accounting.cash-flow'],
        'analytics'       => ['analytics.financial-ratios', 'analytics.revenue-expense-trends', 'analytics.sales', 'analytics.purchasing', 'analytics.inventory', 'analytics.profitability', 'analytics.budget-vs-actual-trend', 'analytics.cash-flow-trend'],
        'bi'              => ['bi.true-total-cost', 'bi.customer-lifetime-value', 'bi.employee-productivity', 'bi.branch-profitability'],
        'pos'             => ['pos.terminals', 'pos.payment-methods', 'pos.till-sessions', 'pos.returns', 'pos.sales', 'pos.settlements', 'pos.reports', 'pos.eis'],
        'settings'        => ['system-settings', 'admin'],
    ];
    $activeSection = '';
    foreach ($sectionMap as $section => $prefixes) {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                $activeSection = $section;
                break 2;
            }
        }
    }
@endphp

<nav class="flex flex-col h-full">
    <div class="sidebar-brand">
        <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center shadow-sm">
            <span class="text-white font-bold text-sm tracking-tight">CB</span>
        </div>
        <span class="font-semibold text-base tracking-tight text-white">CamelotBooks</span>
    </div>

    <div class="sidebar-scroll"
         x-data="{ openSection: '{{ $activeSection }}' }"
         x-init="$watch('openSection', val => { if (val) { $nextTick(() => {}) } })">

        <a href="{{ route('dashboard') }}"
           class="sidebar-link {{ $dashboardActive ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-label">Sales & Purchasing</div>

        <button type="button" @click="openSection = (openSection === 'sales-purchases') ? '' : 'sales-purchases'"
                class="sidebar-parent {{ $activeSection === 'sales-purchases' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span class="flex-1 text-left">Sales & Purchases</span>
            <svg class="sidebar-chevron" :class="openSection === 'sales-purchases' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'sales-purchases'" x-collapse.duration.300ms>
            @php $prefix = 'accounting.customers'; @endphp
            <a href="{{ route('accounting.customers.index') }}" class="sidebar-child {{ str_starts_with($routeName, $prefix) ? 'active' : '' }}">Customers</a>
            <a href="{{ route('accounting.quotations.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.quotations') ? 'active' : '' }}">Quotations</a>
            <a href="{{ route('accounting.invoices.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.invoices') ? 'active' : '' }}">Invoices</a>
            <a href="{{ route('accounting.sales-receipts.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.sales-receipts') ? 'active' : '' }}">Sales Receipts</a>
            <a href="{{ route('accounting.credit-notes.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.credit-notes') ? 'active' : '' }}">Credit Notes</a>
            <div class="mx-5 my-1 border-t border-neutral-800/50"></div>
            <a href="{{ route('accounting.vendors.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.vendors') ? 'active' : '' }}">Vendors</a>
            <a href="{{ route('accounting.purchase-orders.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.purchase-orders') ? 'active' : '' }}">Purchase Orders</a>
            <a href="{{ route('accounting.goods-received-notes.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.goods-received-notes') ? 'active' : '' }}">GRNs</a>
            <a href="{{ route('accounting.bills.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.bills') ? 'active' : '' }}">Bills</a>
            <a href="{{ route('accounting.expenses.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.expenses') ? 'active' : '' }}">Expenses</a>
            <a href="{{ route('accounting.vendor-centre.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.vendor-centre') ? 'active' : '' }}">Vendor Centre</a>
            <a href="{{ route('accounting.products.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.products') ? 'active' : '' }}">Products</a>
        </div>

        <div class="sidebar-section-label">Inventory</div>

        <button type="button" @click="openSection = (openSection === 'inventory') ? '' : 'inventory'"
                class="sidebar-parent {{ $activeSection === 'inventory' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span class="flex-1 text-left">Inventory</span>
            <svg class="sidebar-chevron" :class="openSection === 'inventory' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'inventory'" x-collapse.duration.300ms>
            <a href="{{ route('accounting.inventory-items.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.inventory-items') ? 'active' : '' }}">Items</a>
            <a href="{{ route('accounting.stock-adjustments.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.stock-adjustments') ? 'active' : '' }}">Adjustments</a>
            <a href="{{ route('accounting.stock-transfers.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.stock-transfers') ? 'active' : '' }}">Transfers</a>
            <a href="{{ route('accounting.stock-counts.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.stock-counts') ? 'active' : '' }}">Stock Counts</a>
            <a href="{{ route('accounting.inventory-valuation.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.inventory-valuation') ? 'active' : '' }}">Valuation</a>
        </div>

        <div class="sidebar-section-label">Banking</div>

        <button type="button" @click="openSection = (openSection === 'banking') ? '' : 'banking'"
                class="sidebar-parent {{ $activeSection === 'banking' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            <span class="flex-1 text-left">Banking</span>
            <svg class="sidebar-chevron" :class="openSection === 'banking' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'banking'" x-collapse.duration.300ms>
            <a href="{{ route('accounting.bank-accounts.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.bank-accounts') ? 'active' : '' }}">Bank Accounts</a>
            <a href="{{ route('accounting.bank-reconciliation.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.bank-reconciliation') ? 'active' : '' }}">Bank Reconciliation</a>
            <a href="{{ route('accounting.deposits.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.deposits') ? 'active' : '' }}">Deposits</a>
            <a href="{{ route('accounting.cheques.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.cheques') ? 'active' : '' }}">Cheques</a>
            <a href="{{ route('accounting.petty-cash.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.petty-cash') ? 'active' : '' }}">Petty Cash</a>
            <a href="{{ route('accounting.cash-position.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.cash-position') ? 'active' : '' }}">Cash Position</a>
        </div>

        <div class="sidebar-section-label">Accounting</div>

        <button type="button" @click="openSection = (openSection === 'accounting') ? '' : 'accounting'"
                class="sidebar-parent {{ $activeSection === 'accounting' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span class="flex-1 text-left">Accounting</span>
            <svg class="sidebar-chevron" :class="openSection === 'accounting' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'accounting'" x-collapse.duration.300ms>
            <a href="{{ route('accounting.accounts.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.accounts') ? 'active' : '' }}">Chart of Accounts</a>
            <a href="{{ route('accounting.journal-entries.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.journal-entries') ? 'active' : '' }}">Journal Entries</a>
            <a href="{{ route('accounting.general-ledger.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.general-ledger') ? 'active' : '' }}">General Ledger</a>
            <a href="{{ route('accounting.trial-balance.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.trial-balance') ? 'active' : '' }}">Trial Balance</a>
            <a href="{{ route('accounting.budgets.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.budgets') ? 'active' : '' }}">Budgets</a>
        </div>

        <div class="sidebar-section-label">Fixed Assets</div>

        <button type="button" @click="openSection = (openSection === 'fixed-assets') ? '' : 'fixed-assets'"
                class="sidebar-parent {{ $activeSection === 'fixed-assets' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="flex-1 text-left">Fixed Assets</span>
            <svg class="sidebar-chevron" :class="openSection === 'fixed-assets' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'fixed-assets'" x-collapse.duration.300ms>
            <a href="{{ route('accounting.fixed-assets.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.fixed-assets') ? 'active' : '' }}">Asset Register</a>
            <a href="{{ route('accounting.depreciation.runs') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.depreciation') ? 'active' : '' }}">Depreciation</a>
        </div>

        <div class="sidebar-section-label">Payroll</div>

        <button type="button" @click="openSection = (openSection === 'payroll') ? '' : 'payroll'"
                class="sidebar-parent {{ $activeSection === 'payroll' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span class="flex-1 text-left">Payroll</span>
            <svg class="sidebar-chevron" :class="openSection === 'payroll' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'payroll'" x-collapse.duration.300ms>
            <a href="{{ route('accounting.employees.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.employees') ? 'active' : '' }}">Employees</a>
            <a href="{{ route('accounting.payroll-runs.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.payroll-runs') ? 'active' : '' }}">Payroll Runs</a>
        </div>

        <div class="sidebar-section-label">Reports</div>

        <button type="button" @click="openSection = (openSection === 'reports') ? '' : 'reports'"
                class="sidebar-parent {{ $activeSection === 'reports' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span class="flex-1 text-left">Reports</span>
            <svg class="sidebar-chevron" :class="openSection === 'reports' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'reports'" x-collapse.duration.300ms>
            <a href="{{ route('accounting.report-center.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.report') ? 'active' : '' }}">Report Center</a>
            <a href="{{ route('accounting.income-statement.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.income-statement') ? 'active' : '' }}">Income Statement</a>
            <a href="{{ route('accounting.balance-sheet.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.balance-sheet') ? 'active' : '' }}">Balance Sheet</a>
            <a href="{{ route('accounting.cash-flow.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'accounting.cash-flow') ? 'active' : '' }}">Cash Flow</a>
        </div>

        <div class="sidebar-section-label">Analytics</div>

        <button type="button" @click="openSection = (openSection === 'analytics') ? '' : 'analytics'"
                class="sidebar-parent {{ $activeSection === 'analytics' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            <span class="flex-1 text-left">Analytics</span>
            <svg class="sidebar-chevron" :class="openSection === 'analytics' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'analytics'" x-collapse.duration.300ms>
            <a href="{{ route('analytics.financial-ratios') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.financial-ratios') ? 'active' : '' }}">Financial Ratios</a>
            <a href="{{ route('analytics.revenue-expense-trends') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.revenue-expense-trends') ? 'active' : '' }}">Revenue vs Expense</a>
            <a href="{{ route('analytics.sales') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.sales') ? 'active' : '' }}">Sales Analytics</a>
            <a href="{{ route('analytics.purchasing') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.purchasing') ? 'active' : '' }}">Purchasing Analytics</a>
            <a href="{{ route('analytics.inventory') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.inventory') ? 'active' : '' }}">Inventory Analytics</a>
            <a href="{{ route('analytics.profitability') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.profitability') ? 'active' : '' }}">Profitability</a>
            <a href="{{ route('analytics.budget-vs-actual-trend') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.budget-vs-actual-trend') ? 'active' : '' }}">Budget vs Actual</a>
            <a href="{{ route('analytics.cash-flow-trend') }}" class="sidebar-child {{ str_starts_with($routeName, 'analytics.cash-flow-trend') ? 'active' : '' }}">Cash Flow Trend</a>
        </div>

        <div class="sidebar-section-label">Business Intelligence

</div>

        <button type="button" @click="openSection = (openSection === 'bi') ? '' : 'bi'"
                class="sidebar-parent {{ $activeSection === 'bi' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <span class="flex-1 text-left">BI</span>
            <svg class="sidebar-chevron" :class="openSection === 'bi' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'bi'" x-collapse.duration.300ms>
            <a href="{{ route('bi.true-total-cost') }}" class="sidebar-child {{ str_starts_with($routeName, 'bi.true-total-cost') ? 'active' : '' }}">True Total Cost</a>
            <a href="{{ route('bi.customer-lifetime-value') }}" class="sidebar-child {{ str_starts_with($routeName, 'bi.customer-lifetime-value') ? 'active' : '' }}">Customer LTV</a>
            <a href="{{ route('bi.employee-productivity') }}" class="sidebar-child {{ str_starts_with($routeName, 'bi.employee-productivity') ? 'active' : '' }}">Employee Productivity</a>
            <a href="{{ route('bi.branch-profitability') }}" class="sidebar-child {{ str_starts_with($routeName, 'bi.branch-profitability') ? 'active' : '' }}">Branch Profitability</a>
        </div>

        <div class="sidebar-section-label">Point of Sale</div>

        <button type="button" @click="openSection = (openSection === 'pos') ? '' : 'pos'"
                class="sidebar-parent {{ $activeSection === 'pos' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            <span class="flex-1 text-left">POS</span>
            <svg class="sidebar-chevron" :class="openSection === 'pos' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'pos'" x-collapse.duration.300ms>
            <a href="{{ route('pos.terminals.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.terminals') ? 'active' : '' }}">Terminals</a>
            <a href="{{ route('pos.payment-methods.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.payment-methods') ? 'active' : '' }}">Payment Methods</a>
            <a href="{{ route('pos.till-sessions.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.till-sessions') ? 'active' : '' }}">Till Sessions</a>
            <a href="{{ route('pos.returns.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.returns') ? 'active' : '' }}">Returns</a>
            <a href="{{ route('pos.settlements.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.settlements') ? 'active' : '' }}">Settlements</a>
            <a href="{{ route('pos.reports.x-report') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.reports') ? 'active' : '' }}">Reports</a>
            <a href="{{ route('pos.eis.terminals') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.eis') ? 'active' : '' }}">EIS Terminals</a>
            <a href="{{ route('pos.eis.submissions') }}" class="sidebar-child {{ str_starts_with($routeName, 'pos.eis.submissions') ? 'active' : '' }}">EIS Submissions</a>
        </div>

        <div class="sidebar-section-label">Settings</div>

        <button type="button" @click="openSection = (openSection === 'settings') ? '' : 'settings'"
                class="sidebar-parent {{ $activeSection === 'settings' ? 'active' : '' }}">
            <svg class="icon w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1 text-left">Settings</span>
            <svg class="sidebar-chevron" :class="openSection === 'settings' ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="openSection === 'settings'" x-collapse.duration.300ms>
            <a href="{{ route('system-settings.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'system-settings') ? 'active' : '' }}">System Settings</a>
            <a href="{{ route('admin.numbering-sequences.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'admin.numbering-sequences') ? 'active' : '' }}">Numbering Sequences</a>
            <a href="{{ route('admin.audit-log.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'admin.audit-log') ? 'active' : '' }}">Audit Log</a>
            <a href="{{ route('admin.security.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'admin.security') ? 'active' : '' }}">Security</a>
            <a href="{{ route('admin.notifications.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'admin.notifications') ? 'active' : '' }}">Notifications</a>
            <a href="{{ route('admin.backups.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'admin.backups') ? 'active' : '' }}">Backups</a>
            <a href="{{ route('admin.system-health.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'admin.system-health') ? 'active' : '' }}">System Health</a>
            <a href="{{ route('admin.users.index') }}" class="sidebar-child {{ str_starts_with($routeName, 'admin.users') ? 'active' : '' }}">Users</a>
        </div>
    </div>

    <div class="shrink-0 border-t border-neutral-800/50 p-4">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-full bg-accent/20 flex items-center justify-center">
                <span class="text-xs font-semibold text-accent-300">{{ substr(Auth::user()?->name ?? 'U', 0, 1) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-neutral-300 truncate">{{ Auth::user()?->name ?? 'User' }}</p>
                <p class="text-[11px] text-neutral-500">{{ Auth::user()?->role_in_current_company ?? 'accountant' }}</p>
            </div>
        </div>
    </div>
</nav>
