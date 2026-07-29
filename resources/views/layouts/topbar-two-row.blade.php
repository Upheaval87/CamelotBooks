@php
    $dashboardActive = request()->routeIs('dashboard');
    $currentRoute = request()->route();
    $routeName = $currentRoute ? $currentRoute->getName() : '';

    $primaryNav = [
        ['label' => __('Dashboard'),  'route' => 'dashboard',                     'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['label' => __('Payments'),   'route' => 'accounting.bills.index',         'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        ['label' => __('Invoices'),   'route' => 'accounting.invoices.index',      'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => __('Customers'),  'route' => 'accounting.customers.index',     'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => __('Journal'),    'route' => 'accounting.journal-entries.index','icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ['label' => __('Reports'),    'route' => 'accounting.report-center.index',  'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    ];

    $overflowNav = [
        ['label' => __('Inventory'),      'route' => 'accounting.inventory-items.index',       'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['label' => __('Banking'),        'route' => 'accounting.bank-accounts.index',         'icon' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
        ['label' => __('Chart of Accounts'), 'route' => 'accounting.accounts.index',           'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ['label' => __('General Ledger'),'route' => 'accounting.general-ledger.index',        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['label' => __('Trial Balance'), 'route' => 'accounting.trial-balance.index',          'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => __('Budgets'),       'route' => 'accounting.budgets.index',                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['label' => __('Fixed Assets'),  'route' => 'accounting.fixed-assets.index',           'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        ['label' => __('Payroll'),       'route' => 'accounting.employees.index',              'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => __('Analytics'),     'route' => 'analytics.financial-ratios',              'icon' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z'],
        ['label' => __('BI'),            'route' => 'bi.true-total-cost',                      'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
        ['label' => __('POS'),           'route' => 'pos.terminals.index',                     'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z'],
        ['label' => __('Settings'),      'route' => 'system-settings.index',                   'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
        ['label' => __('Users'),         'route' => 'admin.users.index',                       'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'],
    ];

    $isActive = fn($route) => $route === $routeName || str_starts_with($routeName, $route) || request()->routeIs($route);

    $user = Auth::user();
    $companyName = $currentCompany->name ?? '';
    $branchName = $currentBranches->first()?->name ?? '';
@endphp

<header class="topbar">
    {{-- Row 1: Brand + Company/User bar --}}
    <div class="topbar-row-1">
        <div class="flex items-center gap-3 h-full px-5 lg:px-8 max-w-8xl mx-auto">
            {{-- Brand mark --}}
            <div class="topbar-brand-mark">
                <span>L</span>
            </div>
            <span class="topbar-system-name">{{ config('app.name', 'CamelotBooks') }}</span>

            {{-- Divider --}}
            <div class="topbar-divider"></div>

            {{-- Company + branch (pulled from session via shared $currentCompany) --}}
            <div class="flex items-center gap-1.5 min-w-0" id="topbar-company-area">
                <span class="topbar-company-name truncate">{{ $companyName }}</span>
                @if($branchName)
                    <span class="topbar-branch-name">· {{ $branchName }}</span>
                @endif
            </div>

            {{-- Spacer --}}
            <div class="flex-1 min-w-0"></div>

            {{-- User area --}}
            <div class="flex items-center gap-3 shrink-0">
                <div class="topbar-user-avatar">
                    <span>{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</span>
                </div>
                <div class="hidden sm:block min-w-0">
                    <p class="topbar-user-name truncate">{{ $user?->name ?? 'User' }}</p>
                    <p class="topbar-user-role">{{ $user?->role_in_current_company ?? __('accountant') }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="topbar-logout-btn" title="{{ __('Log Out') }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Row 2: Navigation links --}}
    <div class="topbar-row-2">
        <div class="flex items-center h-full px-5 lg:px-8 max-w-8xl mx-auto">
            {{-- Invisible placeholder that matches the width of Row 1's divider position --}}
            <div id="topbar-nav-offset" class="topbar-nav-offset"></div>

            {{-- Primary nav links --}}
            <nav class="flex items-center gap-0.5 h-full">
                @foreach($primaryNav as $item)
                    @php $active = $isActive($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="topbar-nav-link @if($active) active @endif"
                       @if($active) aria-current="page" @endif>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/></svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Overflow menu --}}
            <div class="relative ml-auto"
                 x-data="{ open: false }"
                 @keydown.escape.window="open = false"
                 @click.outside="open = false">
                <button type="button"
                        class="topbar-overflow-btn"
                        @click="open = !open"
                        :class="open ? 'active' : ''"
                        title="{{ __('More') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     class="topbar-overflow-dropdown"
                     x-cloak>
                    @foreach($overflowNav as $item)
                        @php $active = $isActive($item['route']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="topbar-overflow-item @if($active) active @endif"
                           @if($active) aria-current="page" @endif>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/></svg>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</header>
