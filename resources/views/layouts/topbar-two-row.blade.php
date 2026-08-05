@php
    $currentRoute = request()->route();
    $routeName = $currentRoute ? $currentRoute->getName() : '';
    $companyId = session('current_company_id') ?? 0;
    $user = Auth::user();
    $isAdmin = $user && $user->hasAnyRole(['system_admin', 'company_admin']);
    $feat = fn($f) => \App\Services\FeatureManagement::isEnabled($companyId, $f);

    $filterFeat = fn($items, $feature) => $feat($feature)
        ? $items
        : array_values(array_filter($items, fn($i) => !in_array($i['route'], [
            'accounting.purchase-orders.index',
            'accounting.purchase-requisitions.index',
            'accounting.goods-received-notes.index',
        ])));

    $purchasingChildren = $filterFeat([
        ['label' => __('Vendors'),           'route' => 'accounting.vendors.index'],
        ['label' => __('Purchase Orders'),   'route' => 'accounting.purchase-orders.index'],
        ['label' => __('Purchase Requisitions'),'route' => 'accounting.purchase-requisitions.index'],
        ['label' => __('Quotation Requests'),'route' => 'accounting.quotations.index'],
        ['label' => __('GRNs'),              'route' => 'accounting.goods-received-notes.index'],
        ['label' => __('Bills'),             'route' => 'accounting.bills.index'],
        ['label' => __('Vendor Credits'),    'route' => 'accounting.vendor-credits.index'],
        ['label' => __('Expenses'),          'route' => 'accounting.expenses.index'],
        ['label' => __('Vendor Centre'),     'route' => 'accounting.vendor-centre.index'],
    ], 'purchasing');

    $accountingChildren = [
        ['label' => __('Chart of Accounts'),   'route' => 'accounting.accounts.index'],
        ['label' => __('Journal Entries'),     'route' => 'accounting.journal-entries.index'],
        ['label' => __('General Ledger'),      'route' => 'accounting.general-ledger.index'],
        ['label' => __('Trial Balance'),       'route' => 'accounting.trial-balance.index'],
        ['label' => __('Fiscal Years'),        'route' => 'accounting.fiscal-years.index'],
        ['label' => __('Accounting Periods'),  'route' => 'accounting.periods.index'],
        ['label' => __('Recurring Journals'),  'route' => 'accounting.recurring-journals.index'],
        ['label' => __('Cost Centers'),        'route' => 'accounting.cost-centers.index'],
        ['label' => __('Exchange Rates'),      'route' => 'accounting.exchange-rates.index'],
        ['label' => __('Account Classification'),'route' => 'accounting.account-classification.index'],
    ];
    if ($feat('budgets')) {
        $accountingChildren[] = ['label' => __('Budgets'), 'route' => 'accounting.budgets.index'];
    }

    $modules = [
        (object)[
            'label' => __('Sales'),
            'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
            'children' => [
                ['label' => __('Customers'),      'route' => 'accounting.customers.index'],
                ['label' => __('Quotations'),     'route' => 'accounting.quotations.index'],
                ['label' => __('Invoices'),       'route' => 'accounting.invoices.index'],
                ['label' => __('Sales Receipts'), 'route' => 'accounting.sales-receipts.index'],
                ['label' => __('Credit Notes'),   'route' => 'accounting.credit-notes.index'],
                ['label' => __('Products'),       'route' => 'accounting.products.index'],
            ],
        ],
        (object)[
            'label' => __('Purchasing'),
            'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            'children' => $purchasingChildren,
        ],
    ];

    if ($feat('inventory')) {
        $modules[] = (object)[
            'label' => __('Inventory'),
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'children' => [
                ['label' => __('Items'),           'route' => 'accounting.inventory-items.index'],
                ['label' => __('Item Categories'), 'route' => 'accounting.item-categories.index'],
                ['label' => __('Assemblies'),      'route' => 'accounting.assemblies.index'],
                ['label' => __('Adjustments'),     'route' => 'accounting.stock-adjustments.index'],
                ['label' => __('Transfers'),       'route' => 'accounting.stock-transfers.index'],
                ['label' => __('Stock Counts'),    'route' => 'accounting.stock-counts.index'],
                ['label' => __('UOM Conversions'), 'route' => 'accounting.uom-conversions.index'],
                ['label' => __('Landed Cost'),     'route' => 'accounting.landed-costs.index'],
                ['label' => __('Valuation'),       'route' => 'accounting.inventory-valuation.index'],
                ['label' => __('Low Stock Report'),'route' => 'accounting.low-stock.index'],
            ],
        ];
    }

    if ($feat('banking')) {
        $modules[] = (object)[
            'label' => __('Banking'),
            'icon' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
            'children' => [
                ['label' => __('Bank Accounts'),'route' => 'accounting.bank-accounts.index'],
                ['label' => __('Transfer Funds'),'route' => 'accounting.bank-accounts.transfer-form'],
                ['label' => __('Deposits'),     'route' => 'accounting.deposits.index'],
                ['label' => __('Cheques'),      'route' => 'accounting.cheques.index'],
                ['label' => __('Petty Cash'),   'route' => 'accounting.petty-cash.index'],
                ['label' => __('Cash Position'),'route' => 'accounting.cash-position.index'],
            ],
        ];
    }

    $modules[] = (object)[
        'label' => __('Accounting'),
        'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
        'children' => $accountingChildren,
    ];

    if ($feat('fixed_assets')) {
        $modules[] = (object)[
            'label' => __('Assets'),
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'children' => [
                ['label' => __('Asset Categories'),'route' => 'accounting.asset-categories.index'],
                ['label' => __('Asset Register'),  'route' => 'accounting.fixed-assets.index'],
                ['label' => __('Depreciation'),    'route' => 'accounting.depreciation.runs'],
                ['label' => __('Usage Log (UOP)'), 'route' => 'accounting.asset-usage.index'],
                ['label' => __('Disposals'),       'route' => 'accounting.asset-disposals.index'],
                ['label' => __('Transfers'),       'route' => 'accounting.asset-transfers.index'],
                ['label' => __('Impairments'),     'route' => 'accounting.asset-impairments.index'],
                ['label' => __('Revaluations'),    'route' => 'accounting.asset-revaluations.index'],
            ],
        ];
    }

    if ($feat('payroll')) {
        $modules[] = (object)[
            'label' => __('Payroll'),
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            'children' => [
                ['label' => __('Employees'),      'route' => 'accounting.employees.index'],
                ['label' => __('Payroll Runs'),    'route' => 'accounting.payroll-runs.index'],
                ['label' => __('PAYE Tax Tables'),'route' => 'accounting.paye-tables.index'],
                ['label' => __('Pension Schemes'), 'route' => 'accounting.pension-schemes.index'],
            ],
        ];
    }

    $modules[] = (object)[
        'label' => __('Reports'),
        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'children' => [
            ['label' => __('Report Center'),    'route' => 'accounting.report-center.index'],
            ['label' => __('Income Statement'), 'route' => 'accounting.income-statement.index'],
            ['label' => __('Balance Sheet'),    'route' => 'accounting.balance-sheet.index'],
            ['label' => __('Cash Flow'),        'route' => 'accounting.cash-flow.index'],
            ['label' => __('A/R Aging'),        'route' => 'accounting.aging.ar-summary'],
            ['label' => __('A/P Aging'),        'route' => 'accounting.aging.ap-summary'],
        ],
    ];

    if ($feat('analytics')) {
        $analyticsChildren = [
            ['label' => __('Financial Ratios'),     'route' => 'analytics.financial-ratios'],
            ['label' => __('Revenue vs Expense'),   'route' => 'analytics.revenue-expense-trends'],
            ['label' => __('Sales Analytics'),      'route' => 'analytics.sales'],
            ['label' => __('Profitability'),        'route' => 'analytics.profitability'],
            ['label' => __('Cash Flow Trend'),      'route' => 'analytics.cash-flow-trend'],
        ];
        if ($feat('purchasing')) {
            $analyticsChildren[] = ['label' => __('Purchasing Analytics'), 'route' => 'analytics.purchasing'];
        }
        if ($feat('inventory')) {
            $analyticsChildren[] = ['label' => __('Inventory Analytics'), 'route' => 'analytics.inventory'];
        }
        if ($feat('budgets')) {
            $analyticsChildren[] = ['label' => __('Budget vs Actual'), 'route' => 'analytics.budget-vs-actual-trend'];
        }
        $modules[] = (object)[
            'label' => __('Analytics'),
            'icon' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
            'children' => $analyticsChildren,
        ];
    }

    if ($feat('bi')) {
        $modules[] = (object)[
            'label' => __('BI'),
            'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
            'children' => [
                ['label' => __('True Total Cost'),      'route' => 'bi.true-total-cost'],
                ['label' => __('Customer LTV'),         'route' => 'bi.customer-lifetime-value'],
                ['label' => __('Employee Productivity'),'route' => 'bi.employee-productivity'],
                ['label' => __('Branch Profitability'), 'route' => 'bi.branch-profitability'],
            ],
        ];
    }

    if ($feat('pos')) {
        $modules[] = (object)[
            'label' => __('POS'),
            'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z',
            'children' => [
                ['label' => __('Terminals'),       'route' => 'pos.terminals.index'],
                ['label' => __('Payment Methods'), 'route' => 'pos.payment-methods.index'],
                ['label' => __('Till Sessions'),   'route' => 'pos.till-sessions.index'],
                ['label' => __('Checkout'),        'route' => 'pos.sales.checkout'],
                ['label' => __('Returns'),         'route' => 'pos.returns.index'],
                ['label' => __('Settlements'),     'route' => 'pos.settlements.index'],
                ['label' => __('X-Report'),        'route' => 'pos.reports.x-report'],
                ['label' => __('Z-Report'),        'route' => 'pos.reports.z-report'],
                ['label' => __('Sales by Terminal'),'route' => 'pos.reports.sales-by-terminal'],
                ['label' => __('Sales by Cashier'), 'route' => 'pos.reports.sales-by-cashier'],
                ['label' => __('EIS Terminals'),   'route' => 'pos.eis.terminals'],
                ['label' => __('EIS Submissions'), 'route' => 'pos.eis.submissions'],
            ],
        ];
    }

    if ($isAdmin) {
        $modules[] = (object)[
            'label' => __('System'),
            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'children' => [
                ['label' => __('System Settings'),       'route' => 'system-settings.index'],
                ['label' => __('Features'),              'route' => 'system-settings.features'],
                ['label' => __('Settings Audit Log'),    'route' => 'system-settings.audit-log'],
                ['label' => __('Numbering Sequences'),   'route' => 'admin.numbering-sequences.index'],
                ['label' => __('Security'),              'route' => 'admin.security.index'],
                ['label' => __('Notifications'),         'route' => 'admin.notifications.index'],
                ['label' => __('Backups'),               'route' => 'admin.backups.index'],
                ['label' => __('System Health'),         'route' => 'admin.system-health.index'],
                ['label' => __('Audit Log'),             'route' => 'admin.audit-log.index'],
                ['label' => __('Users & Roles'),         'route' => 'admin.users.index'],
                ['label' => __('Permission Manager'),    'route' => 'admin.permissions.index'],
                ['label' => __('Setup Wizard'),          'route' => 'admin.setup-wizard.index'],
                ['label' => __('Companies'),             'route' => 'companies.index'],
                ['label' => __('Branches'),              'route' => 'branches.index'],
            ],
        ];
    }

    $isActiveRoute = fn($route) => $route === $routeName || str_starts_with($routeName, $route) || request()->routeIs($route);

    $currentCompany ??= null;
    $currentBranches ??= collect();
    $branchName = $currentBranches->first()?->name ?? '';
@endphp

<header class="topbar">
    {{-- Row 1: Brand + Company/User bar --}}
    <div class="topbar-row-1">
        <div class="flex items-center gap-3 h-full px-5 lg:px-8 max-w-8xl mx-auto">
            <div class="topbar-brand-mark">
                <span>L</span>
            </div>
            <span class="topbar-system-name">{{ config('app.name', 'CamelotBooks') }}</span>

            <div class="topbar-divider"></div>

            <div class="flex items-center gap-1.5 min-w-0" id="topbar-company-area">
                <span class="topbar-company-name truncate">{{ $currentCompany?->name ?? config('app.name', 'CamelotBooks') }}</span>
                @if($branchName)
                    <span class="topbar-branch-name">· {{ $branchName }}</span>
                @endif
            </div>

            <div class="flex-1 min-w-0"></div>

            <div class="flex items-center gap-3 shrink-0">
                <x-favourites.dropdown />

                <button type="button"
                        class="topbar-search-btn"
                        title="{{ __('Global Search (Ctrl+K)') }}"
                        onclick="window.dispatchEvent(new CustomEvent('open-global-search'))">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span class="hidden lg:inline">{{ __('Search') }}</span>
                    <kbd>Ctrl K</kbd>
                </button>

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

    {{-- Row 2: Module navigation with submenu dropdowns --}}
    <div class="topbar-row-2">
        <div class="flex items-center h-full px-5 lg:px-8 max-w-8xl mx-auto overflow-visible">
            <div id="topbar-nav-offset" class="topbar-nav-offset"></div>

            <nav class="flex items-center gap-0.5 h-full">
                @foreach($modules as $mod)
                    @php $hasChildren = count($mod->children) > 0; @endphp
                    @if($hasChildren)
                        <div class="topbar-nav-dropdown-root"
                             x-data="{ open: false }"
                             @mouseenter="open = true"
                             @mouseleave="open = false">
                            <button type="button"
                                    class="topbar-nav-link"
                                    :class="open ? 'active' : ''"
                                    @click.prevent="open = !open">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $mod->icon }}"/></svg>
                                <span>{{ $mod->label }}</span>
                                <svg class="w-3 h-3 ml-0.5 opacity-50 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-0.5"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-0.5"
                                 class="topbar-nav-dropdown"
                                 @mouseenter="open = true"
                                 @mouseleave="open = false"
                                 x-cloak>
                                @foreach($mod->children as $child)
                                    @php $cActive = $isActiveRoute($child['route']); @endphp
                                    <a href="{{ route($child['route']) }}"
                                       class="topbar-nav-dropdown-item @if($cActive) active @endif"
                                       @if($cActive) aria-current="page" @endif>
                                        <span>{{ $child['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        @php $mActive = $isActiveRoute($mod->route); @endphp
                        <a href="{{ route($mod->route) }}"
                           class="topbar-nav-link @if($mActive) active @endif"
                           @if($mActive) aria-current="page" @endif>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $mod->icon }}"/></svg>
                            <span>{{ $mod->label }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Overflow menu --}}
            <div class="relative ml-auto shrink-0"
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
                    <a href="{{ route('dashboard') }}" class="topbar-overflow-item">{{ __('Dashboard') }}</a>
                    <a href="{{ route('todo.index') }}" class="topbar-overflow-item">{{ __('My Tasks') }}</a>
                    @if($user?->is_super_admin)
                        <div class="mx-3 my-1 border-t border-white/10"></div>
                        <a href="{{ route('superadmin.dashboard') }}" class="topbar-overflow-item">{{ __('Super Admin') }}</a>
                    @endif
                    @if($isAdmin)
                        <div class="mx-3 my-1 border-t border-white/10"></div>
                        <a href="{{ route('admin.users.index') }}" class="topbar-overflow-item">{{ __('User Management') }}</a>
                        <a href="{{ route('admin.permissions.index') }}" class="topbar-overflow-item">{{ __('Permissions') }}</a>
                        <a href="{{ route('system-settings.index') }}" class="topbar-overflow-item">{{ __('Settings') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
