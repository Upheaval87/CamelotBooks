@php
    $dashboardActive = request()->routeIs('dashboard');
    $currentRoute = request()->route();
    $routeName = $currentRoute ? $currentRoute->getName() : '';
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
    <div class="flex-1 overflow-y-auto py-2" x-data="{ openSection: '' }">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" class="sidebar-nav-item {{ $dashboardActive ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Dashboard</span>
        </a>

        {{-- Sales & Purchases --}}
        <div class="sidebar-section-label">Sales & Purchases</div>
        <a href="{{ route('accounting.customers.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.customers') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Customers</span>
        </a>
        <a href="{{ route('accounting.quotations.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.quotations') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Quotations</span>
        </a>
        <a href="{{ route('accounting.invoices.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.invoices') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
            <span>Invoices</span>
        </a>
        <a href="{{ route('accounting.sales-receipts.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.sales-receipts') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Sales Receipts</span>
        </a>
        <a href="{{ route('accounting.credit-notes.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.credit-notes') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span>Credit Notes</span>
        </a>

        <div class="mx-5 border-t border-white/10 my-1"></div>

        <a href="{{ route('accounting.vendors.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.vendors') && !str_contains($routeName ?? '', 'centre') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span>Vendors</span>
        </a>
        @if(Route::has('accounting.purchase-orders.index'))
        <a href="{{ route('accounting.purchase-orders.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.purchase-orders') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span>Purchase Orders</span>
        </a>
        @endif
        @if(Route::has('accounting.goods-received-notes.index'))
        <a href="{{ route('accounting.goods-received-notes.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.goods-received-notes') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span>GRNs</span>
        </a>
        @endif
        @if(Route::has('accounting.bills.index'))
        <a href="{{ route('accounting.bills.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.bills') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
            <span>Bills</span>
        </a>
        @endif
        <a href="{{ route('accounting.expenses.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.expenses') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Expenses</span>
        </a>
        @if(Route::has('accounting.vendor-centre.index'))
        <a href="{{ route('accounting.vendor-centre.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.vendor-centre') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Vendor Centre</span>
        </a>
        @endif
        <a href="{{ route('accounting.products.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.products') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span>Products</span>
        </a>

        {{-- Inventory --}}
        <div class="sidebar-section-label">Inventory</div>
        @if(Route::has('accounting.inventory-items.index'))
        <a href="{{ route('accounting.inventory-items.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.inventory-items') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span>Items</span>
        </a>
        @endif
        @if(Route::has('accounting.stock-adjustments.index'))
        <a href="{{ route('accounting.stock-adjustments.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.stock-adjustments') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            <span>Adjustments</span>
        </a>
        @endif
        @if(Route::has('accounting.stock-transfers.index'))
        <a href="{{ route('accounting.stock-transfers.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.stock-transfers') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
            <span>Transfers</span>
        </a>
        @endif
        @if(Route::has('accounting.stock-counts.index'))
        <a href="{{ route('accounting.stock-counts.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.stock-counts') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Stock Counts</span>
        </a>
        @endif
        @if(Route::has('accounting.inventory-valuation.index'))
        <a href="{{ route('accounting.inventory-valuation.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.inventory-valuation') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Valuation</span>
        </a>
        @endif

        {{-- Banking --}}
        <div class="sidebar-section-label">Banking</div>
        <a href="{{ route('accounting.bank-accounts.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.bank-accounts') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3m4-3v3m4-3v3"/></svg>
            <span>Bank Accounts</span>
        </a>
        <a href="{{ route('accounting.deposits.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.deposits') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1m0-13a9 9 0 100 18 9 9 0 000-18z"/></svg>
            <span>Deposits</span>
        </a>
        @if(Route::has('accounting.cheques.index'))
        <a href="{{ route('accounting.cheques.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.cheques') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Cheques</span>
        </a>
        @endif
        @if(Route::has('accounting.petty-cash.index'))
        <a href="{{ route('accounting.petty-cash.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.petty-cash') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
            <span>Petty Cash</span>
        </a>
        @endif
        <a href="{{ route('accounting.cash-position.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.cash-position') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span>Cash Position</span>
        </a>

        {{-- Accounting --}}
        <div class="sidebar-section-label">Accounting</div>
        <a href="{{ route('accounting.accounts.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.accounts') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span>Chart of Accounts</span>
        </a>
        <a href="{{ route('accounting.journal-entries.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.journal-entries') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Journal Entries</span>
        </a>
        <a href="{{ route('accounting.general-ledger.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.general-ledger') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <span>General Ledger</span>
        </a>
        <a href="{{ route('accounting.trial-balance.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.trial-balance') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span>Trial Balance</span>
        </a>
        @if(Route::has('accounting.budgets.index'))
        <a href="{{ route('accounting.budgets.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.budgets') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Budgets</span>
        </a>
        @endif

        {{-- Fixed Assets --}}
        <div class="sidebar-section-label">Fixed Assets</div>
        <a href="{{ route('accounting.fixed-assets.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.fixed-assets') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span>Asset Register</span>
        </a>
        <a href="{{ route('accounting.depreciation.runs') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.asset-depreciation') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
            <span>Depreciation</span>
        </a>

        {{-- Payroll --}}
        <div class="sidebar-section-label">Payroll</div>
        <a href="{{ route('accounting.employees.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.employees') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Employees</span>
        </a>
        <a href="{{ route('accounting.payroll-runs.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.payroll-runs') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Payroll Runs</span>
        </a>

        {{-- Reports --}}
        <div class="sidebar-section-label">Reports</div>
        <a href="{{ route('accounting.report-center.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.report') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Report Center</span>
        </a>
        <a href="{{ route('accounting.income-statement.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.income-statement') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Income Statement</span>
        </a>
        <a href="{{ route('accounting.balance-sheet.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.balance-sheet') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span>Balance Sheet</span>
        </a>
        <a href="{{ route('accounting.cash-flow.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'accounting.cash-flow') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span>Cash Flow</span>
        </a>

        {{-- Analytics --}}
        @if(Route::has('analytics.financial-ratios'))
        <div class="sidebar-section-label">Analytics</div>
        <a href="{{ route('analytics.financial-ratios') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'analytics') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Analytics</span>
        </a>
        @endif

        {{-- Settings --}}
        <div class="sidebar-section-label">Settings</div>
        @if(Route::has('system-settings.index'))
        <a href="{{ route('system-settings.index', 'company') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'system-settings') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Settings</span>
        </a>
        @endif
        @if(Route::has('admin.audit-log.index'))
        <a href="{{ route('admin.audit-log.index') }}" class="sidebar-nav-item {{ str_starts_with($routeName ?? '', 'admin') ? 'active' : '' }}">
            <svg class="sidebar-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Admin</span>
        </a>
        @endif
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
