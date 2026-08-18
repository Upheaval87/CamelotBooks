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
        ['label' => __('Payments'),          'route' => 'accounting.vendor-payments.index'],
        ['label' => __('Vendor Credits'),    'route' => 'accounting.vendor-credits.index'],
        ['label' => __('Expenses'),          'route' => 'accounting.expenses.dashboard', 'active' => 'accounting.expenses'],
        ['label' => __('Vendor Centre'),     'route' => 'accounting.vendors.dashboard'],
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
        $accountingChildren[] = ['label' => __('Budgeting'), 'route' => 'accounting.budgets.dashboard', 'active' => 'accounting.budgets.'];
    }

    $modules = [
        (object)[
            'label' => __('Sales'),
            'icon' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
            'children' => [
                ['label' => __('Customers'),      'route' => 'accounting.customers.index'],
                ['label' => __('Quotations'),     'route' => 'accounting.quotations.index'],
                ['label' => __('Sales Orders'),   'route' => 'accounting.sales-orders.index'],
                ['label' => __('Invoices'),       'route' => 'accounting.invoices.index'],
                ['label' => __('Sales Receipts'), 'route' => 'accounting.sales-receipts.index'],
                ['label' => __('Credit Notes'),   'route' => 'accounting.credit-notes.index'],
            ],
        ],
        (object)[
            'label' => __('Purchasing'),
            'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
            'children' => $purchasingChildren,
        ],
    ];

    if ($feat('inventory')) {
        $modules[] = (object)[
            'label' => __('Inventory'),
            'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
            'children' => [
                ['label' => __('Dashboard'),              'route' => 'accounting.inventory.dashboard'],
                ['label' => __('Items'),                  'route' => 'accounting.inventory.items'],
                ['label' => __('Categories'),             'route' => 'accounting.invsetup.categories'],
                ['label' => __('Assemblies'),             'route' => 'accounting.invsetup.assemblies'],
                ['label' => __('Transfers'),              'route' => 'accounting.invsetup.transfers'],
                ['label' => __('Adjustments'),            'route' => 'accounting.invsetup.adjustments'],
                ['label' => __('Stock Count'),            'route' => 'accounting.invsetup.stockcount'],
                ['label' => __('UOM & Landed Costs'),     'route' => 'accounting.invsetup.uom'],
                ['label' => __('Valuation'),              'route' => 'accounting.invsetup.valuation'],
                ['label' => __('Low Stock'),              'route' => 'accounting.invsetup.lowstock'],
            ],
        ];
    }

    if ($feat('banking')) {
        $modules[] = (object)[
            'label' => __('Banking'),
            'icon' => '<line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
            'children' => [
                ['label' => __('Banking Centre'),'route' => 'accounting.banking.dashboard'],
                ['label' => __('Bank Accounts'),  'route' => 'accounting.banking.accounts'],
                ['label' => __('Petty Cash'),     'route' => 'accounting.banking.petty'],
                ['label' => __('Bank Reconciliation'),'route' => 'accounting.bank-reconciliation.index'],
                ['label' => __('Cash Position'),'route' => 'accounting.cash-position.index'],
            ],
        ];
    }

    $modules[] = (object)[
        'label' => __('Accounting'),
        'icon' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'children' => $accountingChildren,
    ];

    if ($feat('fixed_assets')) {
        $modules[] = (object)[
            'label' => __('Assets'),
            'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
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
            'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
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
        'icon' => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
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
        $modules[] = (object)[
            'label' => __('Analytics'),
            'icon' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
            'children' => $analyticsChildren,
        ];
    }

    if ($feat('bi')) {
        $modules[] = (object)[
            'label' => __('BI'),
            'icon' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
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
            'icon' => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
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
            'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.32 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
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
                ['label' => __('Branch Requests'),       'route' => 'branch-requests.index'],
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
                <button type="button"
                        class="fav-star-trigger todo-trigger"
                        x-data
                        @click="$dispatch('open-modal', 'my-tasks')"
                        title="{{ __('My Tasks') }}"
                        aria-haspopup="dialog">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="hidden lg:inline">{{ __('My Tasks') }}</span>
                    <span id="todo-trigger-count" class="fav-count">{{ \App\Models\TodoTask::forCompany($companyId)->forUser($user?->id ?? 0)->active()->count() }}</span>
                </button>

                <x-favourites.dropdown />

                <div class="topbar-font-scale hidden md:flex"
                     x-data="fontScaleControl({ current: '{{ $user?->font_scale ?? 1 }}', steps: @js(\App\Models\User::FONT_STEPS), labels: @js(\App\Models\User::FONT_STEP_LABELS) })"
                     role="group"
                     aria-label="{{ __('Font size') }}">
                    <button type="button"
                            class="topbar-font-scale-btn"
                            :disabled="atMin"
                            @click="setStep(-1)"
                            title="{{ __('Decrease font size') }}"
                            aria-label="{{ __('Decrease font size') }}">
                        A&minus;
                    </button>
                    <button type="button"
                            class="topbar-font-scale-btn"
                            :disabled="atMax"
                            @click="setStep(1)"
                            title="{{ __('Increase font size') }}"
                            aria-label="{{ __('Increase font size') }}">
                        A+
                    </button>
                </div>

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
                             :class="open ? 'open' : ''"
                             @mouseenter="open = true"
                             @mouseleave="open = false">
                            <button type="button"
                                    class="topbar-nav-link"
                                    :class="open ? 'active' : ''"
                                    @click.prevent="open = !open">
                                 <svg class="ni" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $mod->icon !!}</svg>
                                 <span>{{ $mod->label }}</span>
                                 <svg class="nc" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
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
                                    @php $cActive = $isActiveRoute($child['route']) || (($child['active'] ?? null) && str_starts_with($routeName, $child['active'])); @endphp
                                    @if($child['moved'] ?? false)
                                        <div class="dd-sep"></div>
                                    @endif
                                    <a href="{{ route($child['route']) }}"
                                       class="topbar-nav-dropdown-item @if($cActive) active @endif @if($child['moved'] ?? false) moved @endif"
                                       @if($cActive) aria-current="page" @endif>
                                        <span>{{ $child['label'] }}{!! ($child['moved'] ?? false) ? ' &#8594;' : '' !!}</span>
                                        @if($child['moved'] ?? false)
                                            <span class="tagnew">MOVED</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        @php $mActive = $isActiveRoute($mod->route); @endphp
                        <a href="{{ route($mod->route) }}"
                           class="topbar-nav-link @if($mActive) active @endif"
                           @if($mActive) aria-current="page" @endif>
                             <svg class="ni" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $mod->icon !!}</svg>
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
