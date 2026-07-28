@php
    $dashboardActive = request()->routeIs('dashboard');
    $currentRoute = request()->route();
    $routeName = $currentRoute ? $currentRoute->getName() : '';

    $companyId = session('current_company_id') ?? 0;

    $sectionMap = [
        'sales-purchases' => ['accounting.customers', 'accounting.quotations', 'accounting.invoices', 'accounting.sales-receipts', 'accounting.credit-notes', 'accounting.vendors', 'accounting.purchase-orders', 'accounting.goods-received-notes', 'accounting.bills', 'accounting.expenses', 'accounting.vendor-centre', 'accounting.products'],
        'inventory'       => ['accounting.inventory-items', 'accounting.stock-adjustments', 'accounting.stock-transfers', 'accounting.stock-counts', 'accounting.inventory-valuation'],
        'banking'         => ['accounting.bank-accounts', 'accounting.deposits', 'accounting.cheques', 'accounting.petty-cash', 'accounting.cash-position'],
        'accounting'      => ['accounting.accounts', 'accounting.journal-entries', 'accounting.general-ledger', 'accounting.trial-balance', 'accounting.budgets'],
        'fixed-assets'    => ['accounting.fixed-assets', 'accounting.asset-depreciation', 'accounting.depreciation'],
        'payroll'         => ['accounting.employees', 'accounting.payroll-runs'],
        'reports'         => ['accounting.report', 'accounting.income-statement', 'accounting.balance-sheet', 'accounting.cash-flow'],
        'analytics'       => ['analytics.financial-ratios', 'analytics.revenue-expense-trends', 'analytics.sales', 'analytics.purchasing', 'analytics.inventory', 'analytics.profitability', 'analytics.budget-vs-actual', 'analytics.cash-flow-trend'],
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

<nav class="flex flex-col h-full bg-atlas-navy">

    {{-- Brand --}}
    <div class="flex items-center gap-3 px-5 h-16 shrink-0 border-b border-white/10">
        <div class="w-8 h-8 rounded-lg bg-atlas-amber flex items-center justify-center">
            <span class="text-white font-bold text-sm">CB</span>
        </div>
        <span class="text-atlas-amber font-semibold text-base tracking-wide">CamelotBooks</span>
    </div>

    {{-- Nav items --}}
    <div class="flex-1 overflow-y-auto py-2"
         x-data="{ openSection: '{{ $activeSection }}' }"
         x-init="$watch('openSection', val => { if (val) { $nextTick(() => {}) } })">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" class="sidebar-nav-item {{ $dashboardActive ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Dashboard</span>
        </a>

        {{-- Sales & Purchases --}}
        <button type="button"
                @click="openSection = (openSection === 'sales-purchases') ? '' : 'sales-purchases'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'sales-purchases' || '{{ $activeSection }}' === 'sales-purchases' }"
                aria-controls="section-sales-purchases"
                :aria-expanded="openSection === 'sales-purchases'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1 text-left">Sales & Purchases</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'sales-purchases' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-sales-purchases"
             x-show="openSection === 'sales-purchases'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'sales-purchases'">
            <a href="{{ route('accounting.customers.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.customers') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Customers</span>
            </a>
            <a href="{{ route('accounting.quotations.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.quotations') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Quotations</span>
            </a>
            <a href="{{ route('accounting.invoices.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.invoices') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <span>Invoices</span>
            </a>
            <a href="{{ route('accounting.sales-receipts.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.sales-receipts') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Sales Receipts</span>
            </a>
            <a href="{{ route('accounting.credit-notes.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.credit-notes') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Credit Notes</span>
            </a>
            <div class="mx-5 border-t border-white/10 my-1"></div>
            <a href="{{ route('accounting.vendors.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.vendors') && !str_contains($routeName ?? '', 'centre') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Vendors</span>
            </a>
            @if(Route::has('accounting.purchase-orders.index'))
            <a href="{{ route('accounting.purchase-orders.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.purchase-orders') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Purchase Orders</span>
            </a>
            @endif
            @if(Route::has('accounting.goods-received-notes.index'))
            <a href="{{ route('accounting.goods-received-notes.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.goods-received-notes') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>GRNs</span>
            </a>
            @endif
            @if(Route::has('accounting.bills.index'))
            <a href="{{ route('accounting.bills.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.bills') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <span>Bills</span>
            </a>
            @endif
            <a href="{{ route('accounting.expenses.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.expenses') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Expenses</span>
            </a>
            @if(Route::has('accounting.vendor-centre.index'))
            <a href="{{ route('accounting.vendor-centre.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.vendor-centre') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>Vendor Centre</span>
            </a>
            @endif
            <a href="{{ route('accounting.products.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.products') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Products</span>
            </a>
        </div>

        {{-- Inventory --}}
        <button type="button"
                @click="openSection = (openSection === 'inventory') ? '' : 'inventory'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'inventory' || '{{ $activeSection }}' === 'inventory' }"
                aria-controls="section-inventory"
                :aria-expanded="openSection === 'inventory'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span class="flex-1 text-left">Inventory</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'inventory' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-inventory"
             x-show="openSection === 'inventory'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'inventory'">
            @if(Route::has('accounting.inventory-items.index'))
            <a href="{{ route('accounting.inventory-items.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.inventory-items') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Items</span>
            </a>
            @endif
            @if(Route::has('accounting.stock-adjustments.index'))
            <a href="{{ route('accounting.stock-adjustments.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.stock-adjustments') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Adjustments</span>
            </a>
            @endif
            @if(Route::has('accounting.stock-transfers.index'))
            <a href="{{ route('accounting.stock-transfers.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.stock-transfers') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                <span>Transfers</span>
            </a>
            @endif
            @if(Route::has('accounting.stock-counts.index'))
            <a href="{{ route('accounting.stock-counts.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.stock-counts') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Stock Counts</span>
            </a>
            @endif
            @if(Route::has('accounting.inventory-valuation.index'))
            <a href="{{ route('accounting.inventory-valuation.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.inventory-valuation') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Valuation</span>
            </a>
            @endif
        </div>

        {{-- Banking --}}
        <button type="button"
                @click="openSection = (openSection === 'banking') ? '' : 'banking'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'banking' || '{{ $activeSection }}' === 'banking' }"
                aria-controls="section-banking"
                :aria-expanded="openSection === 'banking'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3m4-3v3m4-3v3"/></svg>
            <span class="flex-1 text-left">Banking</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'banking' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-banking"
             x-show="openSection === 'banking'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'banking'">
            <a href="{{ route('accounting.bank-accounts.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.bank-accounts') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3m4-3v3m4-3v3"/></svg>
                <span>Bank Accounts</span>
            </a>
            <a href="{{ route('accounting.deposits.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.deposits') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1m0-13a9 9 0 100 18 9 9 0 000-18z"/></svg>
                <span>Deposits</span>
            </a>
            @if(Route::has('accounting.cheques.index'))
            <a href="{{ route('accounting.cheques.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.cheques') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Cheques</span>
            </a>
            @endif
            @if(Route::has('accounting.petty-cash.index'))
            <a href="{{ route('accounting.petty-cash.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.petty-cash') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                <span>Petty Cash</span>
            </a>
            @endif
            <a href="{{ route('accounting.cash-position.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.cash-position') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span>Cash Position</span>
            </a>
        </div>

        {{-- Accounting --}}
        <button type="button"
                @click="openSection = (openSection === 'accounting') ? '' : 'accounting'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'accounting' || '{{ $activeSection }}' === 'accounting' }"
                aria-controls="section-accounting"
                :aria-expanded="openSection === 'accounting'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span class="flex-1 text-left">Accounting</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'accounting' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-accounting"
             x-show="openSection === 'accounting'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'accounting'">
            <a href="{{ route('accounting.accounts.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.accounts') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Chart of Accounts</span>
            </a>
            <a href="{{ route('accounting.journal-entries.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.journal-entries') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Journal Entries</span>
            </a>
            <a href="{{ route('accounting.general-ledger.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.general-ledger') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>General Ledger</span>
            </a>
            <a href="{{ route('accounting.trial-balance.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.trial-balance') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Trial Balance</span>
            </a>
            @if(Route::has('accounting.budgets.index'))
            <a href="{{ route('accounting.budgets.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.budgets') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Budgets</span>
            </a>
            @endif
        </div>

        {{-- Fixed Assets --}}
        <button type="button"
                @click="openSection = (openSection === 'fixed-assets') ? '' : 'fixed-assets'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'fixed-assets' || '{{ $activeSection }}' === 'fixed-assets' }"
                aria-controls="section-fixed-assets"
                :aria-expanded="openSection === 'fixed-assets'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="flex-1 text-left">Fixed Assets</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'fixed-assets' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-fixed-assets"
             x-show="openSection === 'fixed-assets'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'fixed-assets'">
            <a href="{{ route('accounting.fixed-assets.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.fixed-assets') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Asset Register</span>
            </a>
            <a href="{{ route('accounting.depreciation.runs') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.asset-depreciation') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                <span>Depreciation</span>
            </a>
        </div>

        {{-- Payroll --}}
        <button type="button"
                @click="openSection = (openSection === 'payroll') ? '' : 'payroll'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'payroll' || '{{ $activeSection }}' === 'payroll' }"
                aria-controls="section-payroll"
                :aria-expanded="openSection === 'payroll'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1 text-left">Payroll</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'payroll' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-payroll"
             x-show="openSection === 'payroll'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'payroll'">
            <a href="{{ route('accounting.employees.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.employees') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Employees</span>
            </a>
            <a href="{{ route('accounting.payroll-runs.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.payroll-runs') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Payroll Runs</span>
            </a>
        </div>

        {{-- Reports --}}
        <button type="button"
                @click="openSection = (openSection === 'reports') ? '' : 'reports'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'reports' || '{{ $activeSection }}' === 'reports' }"
                aria-controls="section-reports"
                :aria-expanded="openSection === 'reports'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="flex-1 text-left">Reports</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'reports' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-reports"
             x-show="openSection === 'reports'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'reports'">
            <a href="{{ route('accounting.report-center.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.report') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Report Center</span>
            </a>
            <a href="{{ route('accounting.income-statement.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.income-statement') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Income Statement</span>
            </a>
            <a href="{{ route('accounting.balance-sheet.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.balance-sheet') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Balance Sheet</span>
            </a>
            <a href="{{ route('accounting.cash-flow.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'accounting.cash-flow') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span>Cash Flow</span>
            </a>
        </div>

        {{-- Analytics --}}
        @if(\App\Services\FeatureManagement::isEnabled($companyId, 'analytics'))
        <button type="button"
                @click="openSection = (openSection === 'analytics') ? '' : 'analytics'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'analytics' || '{{ $activeSection }}' === 'analytics' }"
                aria-controls="section-analytics"
                :aria-expanded="openSection === 'analytics'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span class="flex-1 text-left">Analytics</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'analytics' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-analytics"
             x-show="openSection === 'analytics'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'analytics'">
            <a href="{{ route('analytics.financial-ratios') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.financial-ratios') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Financial Ratios</span>
            </a>
            <a href="{{ route('analytics.revenue-expense-trends') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.revenue-expense-trends') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span>Revenue & Expense Trends</span>
            </a>
            <a href="{{ route('analytics.sales') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.sales') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Sales Analytics</span>
            </a>
            @if(\App\Services\FeatureManagement::isEnabled($companyId, 'purchasing'))
            <a href="{{ route('analytics.purchasing') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.purchasing') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Purchasing Analytics</span>
            </a>
            @endif
            @if(\App\Services\FeatureManagement::isEnabled($companyId, 'inventory'))
            <a href="{{ route('analytics.inventory') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.inventory') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Inventory Analytics</span>
            </a>
            @endif
            <a href="{{ route('analytics.profitability') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.profitability') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Profitability Analytics</span>
            </a>
            @if(\App\Services\FeatureManagement::isEnabled($companyId, 'budgets'))
            <a href="{{ route('analytics.budget-vs-actual-trend') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.budget-vs-actual') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Budget vs Actual</span>
            </a>
            @endif
            <a href="{{ route('analytics.cash-flow-trend') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'analytics.cash-flow-trend') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                <span>Cash Flow Trend</span>
            </a>
        </div>
        @endif

        {{-- Business Intelligence --}}
        @if(\App\Services\FeatureManagement::isEnabled($companyId, 'bi'))
        <button type="button"
                @click="openSection = (openSection === 'bi') ? '' : 'bi'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'bi' || '{{ $activeSection }}' === 'bi' }"
                aria-controls="section-bi"
                :aria-expanded="openSection === 'bi'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span class="flex-1 text-left">Business Intelligence</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'bi' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-bi"
             x-show="openSection === 'bi'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'bi'">
            <a href="{{ route('bi.true-total-cost') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'bi.true-total-cost') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>True Total Cost</span>
            </a>
            <a href="{{ route('bi.customer-lifetime-value') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'bi.customer-lifetime-value') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Customer Lifetime Value</span>
            </a>
            <a href="{{ route('bi.employee-productivity') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'bi.employee-productivity') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Employee Productivity</span>
            </a>
            <a href="{{ route('bi.branch-profitability') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'bi.branch-profitability') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Branch Profitability</span>
            </a>
        </div>
        @endif

        {{-- Point of Sale --}}
        @if(\App\Services\FeatureManagement::isEnabled($companyId, 'pos'))
        <button type="button"
                @click="openSection = (openSection === 'pos') ? '' : 'pos'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'pos' || '{{ $activeSection }}' === 'pos' }"
                aria-controls="section-pos"
                :aria-expanded="openSection === 'pos'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            <span class="flex-1 text-left">Point of Sale</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'pos' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-pos"
             x-show="openSection === 'pos'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'pos'">
            <a href="{{ route('pos.terminals.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.terminals') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                <span>Terminals</span>
            </a>
            <a href="{{ route('pos.payment-methods.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.payment-methods') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Payment Methods</span>
            </a>
            <a href="{{ route('pos.till-sessions.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.till-sessions') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1m0-13a9 9 0 100 18 9 9 0 000-18z"/></svg>
                <span>Till Sessions</span>
            </a>
            <a href="{{ route('pos.returns.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.returns') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Returns / Refunds</span>
            </a>
            <a href="{{ route('pos.sales.checkout') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.sales') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Checkout</span>
            </a>
            <a href="{{ route('pos.settlements.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.settlements') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3m4-3v3m4-3v3"/></svg>
                <span>Payment Settlements</span>
            </a>
            <div class="sidebar-section-label">Reports</div>
            <a href="{{ route('pos.reports.x-report') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.reports.x-report') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>X-Report</span>
            </a>
            <a href="{{ route('pos.reports.z-report') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.reports.z-report') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Z-Report</span>
            </a>
            <a href="{{ route('pos.reports.sales-by-terminal') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.reports.sales-by-terminal') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Sales by Terminal</span>
            </a>
            <a href="{{ route('pos.reports.sales-by-cashier') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.reports.sales-by-cashier') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Sales by Cashier</span>
            </a>
            <a href="{{ route('pos.eis.terminals') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.eis') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                <span>EIS Terminals</span>
            </a>
            <a href="{{ route('pos.eis.submissions') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'pos.eis.submissions') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                <span>EIS Submissions</span>
            </a>
        </div>
        @endif

        {{-- Settings --}}
        <button type="button"
                @click="openSection = (openSection === 'settings') ? '' : 'settings'"
                class="sidebar-parent-item"
                :class="{ 'parent-active': openSection === 'settings' || '{{ $activeSection }}' === 'settings' }"
                aria-controls="section-settings"
                :aria-expanded="openSection === 'settings'">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1 text-left">Settings</span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': openSection === 'settings' }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="section-settings"
             x-show="openSection === 'settings'" x-collapse.duration.300ms
             role="region" :aria-hidden="openSection !== 'settings'">
            @if(Route::has('system-settings.index'))
            <a href="{{ route('system-settings.index', 'company') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'system-settings') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Settings</span>
            </a>
            @endif
            @if(Route::has('admin.audit-log.index'))
            <a href="{{ route('admin.audit-log.index') }}" class="sidebar-nav-item sidebar-child-item {{ str_starts_with($routeName ?? '', 'admin') ? 'active' : '' }}">
                <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Admin</span>
            </a>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="shrink-0 border-t border-white/10 px-5 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full bg-atlas-amber/20 flex items-center justify-center shrink-0">
                    <span class="text-atlas-amber text-xs font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-white/40 truncate">{{ ucfirst(str_replace('_', ' ', Auth::user()->role_in_current_company ?? 'User')) }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="text-white/40 hover:text-white transition-colors" title="Log Out">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
        <div class="mt-3 text-center">
            <span class="text-[10px] text-white/25">Powered by NovaCore Systems</span>
        </div>
    </div>
</nav>
