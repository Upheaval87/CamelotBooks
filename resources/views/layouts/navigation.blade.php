<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <div class="hidden space-x-1 sm:-my-px sm:ms-6 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if(session('current_company_id'))
                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.customers.*') || request()->routeIs('accounting.vendors.*') || request()->routeIs('accounting.invoices.*') || request()->routeIs('accounting.bills.*') || request()->routeIs('accounting.products.*') || request()->routeIs('accounting.credit-notes.*') || request()->routeIs('accounting.vendor-credits.*') || request()->routeIs('accounting.customer-payments.*') || request()->routeIs('accounting.vendor-payments.*') || request()->routeIs('accounting.purchase-requisitions.*') || request()->routeIs('accounting.purchase-orders.*') || request()->routeIs('accounting.goods-received-notes.*') || request()->routeIs('accounting.expenses.*') || request()->routeIs('accounting.vendor-centre.*') || request()->routeIs('accounting.quotations.*') || request()->routeIs('accounting.sales-receipts.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Sales & Purchases') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-56 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Sales</div>
                            <a href="{{ route('accounting.customers.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Customers') }}</a>
                            <a href="{{ route('accounting.quotations.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Quotations') }}</a>
                            <a href="{{ route('accounting.invoices.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Invoices') }}</a>
                            <a href="{{ route('accounting.sales-receipts.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Sales Receipts') }}</a>
                            <a href="{{ route('accounting.credit-notes.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Credit Notes') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Purchases</div>
                            <a href="{{ route('accounting.vendors.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Vendors') }}</a>
                            @if(Route::has('accounting.quotation-requests.index'))
                            <a href="{{ route('accounting.quotation-requests.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Quotation Requests') }}</a>
                            @endif
                            <a href="{{ route('accounting.purchase-requisitions.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Purchase Requisitions') }}</a>
                            <a href="{{ route('accounting.purchase-orders.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Purchase Orders') }}</a>
                            <a href="{{ route('accounting.goods-received-notes.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Goods Received Notes') }}</a>
                            <a href="{{ route('accounting.bills.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Bills') }}</a>
                            <a href="{{ route('accounting.vendor-credits.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Vendor Credits') }}</a>
                            <a href="{{ route('accounting.expenses.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Expenses') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            @if(Route::has('accounting.payment-requests.index'))
                            <a href="{{ route('accounting.payment-requests.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Payment Requests') }}</a>
                            @endif
                            <a href="{{ route('accounting.vendor-centre.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100 font-medium">{{ __('Vendor Centre') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.products.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Products & Services') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.inventory-items.*') || request()->routeIs('accounting.stock-adjustments.*') || request()->routeIs('accounting.stock-transfers.*') || request()->routeIs('accounting.inventory-valuation.*') || request()->routeIs('accounting.low-stock.*') || request()->routeIs('accounting.uom-conversions.*') || request()->routeIs('accounting.landed-costs.*') || request()->routeIs('accounting.item-categories.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Inventory') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity5">
                            <a href="{{ route('accounting.inventory-items.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Inventory Items') }}</a>
                            <a href="{{ route('accounting.item-categories.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Item Categories') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.stock-adjustments.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Stock Adjustments') }}</a>
                            <a href="{{ route('accounting.stock-transfers.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Stock Transfers') }}</a>
                            <a href="{{ route('accounting.assemblies.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Assemblies') }}</a>
                            <a href="{{ route('accounting.stock-counts.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Stock Counts') }}</a>
                            <a href="{{ route('accounting.uom-conversions.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('UOM Conversions') }}</a>
                            <a href="{{ route('accounting.landed-costs.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Landed Cost') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.inventory-valuation.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Valuation Report') }}</a>
                            <a href="{{ route('accounting.low-stock.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Low Stock Report') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.bank-accounts.*') || request()->routeIs('accounting.bank-reconciliation.*') || request()->routeIs('accounting.deposits.*') || request()->routeIs('accounting.cheques.*') || request()->routeIs('accounting.petty-cash.*') || request()->routeIs('accounting.cash-position.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Banking') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('accounting.bank-accounts.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Bank Accounts') }}</a>
                            <a href="{{ route('accounting.deposits.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Make Deposits') }}</a>
                            <a href="{{ route('accounting.cheques.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Write Cheques') }}</a>
                            <a href="{{ route('accounting.bank-accounts.transfer-form') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Transfer Funds') }}</a>
                            <a href="{{ route('accounting.petty-cash.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Petty Cash') }}</a>
                            <a href="{{ route('accounting.cash-position.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Cash Position') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.accounts.*') || request()->routeIs('accounting.journal-entries.*') || request()->routeIs('accounting.general-ledger.*') || request()->routeIs('accounting.trial-balance.*') || request()->routeIs('accounting.periods.*') || request()->routeIs('accounting.fiscal-years.*') || request()->routeIs('accounting.recurring-journals.*') || request()->routeIs('accounting.cost-centers.*') || request()->routeIs('accounting.exchange-rates.*') || request()->routeIs('accounting.budgets.*') || request()->routeIs('accounting.account-classification.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Accounting') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('accounting.accounts.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Chart of Accounts') }}</a>
                            <a href="{{ route('accounting.journal-entries.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Journal Entries') }}</a>
                            <a href="{{ route('accounting.general-ledger.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('General Ledger') }}</a>
                            <a href="{{ route('accounting.trial-balance.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Trial Balance') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.fiscal-years.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Fiscal Years') }}</a>
                            <a href="{{ route('accounting.periods.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Accounting Periods') }}</a>
                            <a href="{{ route('accounting.recurring-journals.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Recurring Journals') }}</a>
                            <a href="{{ route('branches.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Branches') }}</a>
                            <a href="{{ route('accounting.cost-centers.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Cost Centers') }}</a>
                            <a href="{{ route('accounting.exchange-rates.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Exchange Rates') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.budgets.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Budgets') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.account-classification.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Account Classification') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none {{ request()->routeIs('accounting.fixed-assets.*') || request()->routeIs('accounting.asset-categories.*') || request()->routeIs('accounting.asset-disposals.*') || request()->routeIs('accounting.asset-transfers.*') || request()->routeIs('accounting.asset-impairments.*') || request()->routeIs('accounting.asset-revaluations.*') || request()->routeIs('accounting.depreciation.*') || request()->routeIs('accounting.asset-usage.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Fixed Assets') }}
                            <svg class="ml-1 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('accounting.asset-categories.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Asset Categories') }}</a>
                            <a href="{{ route('accounting.fixed-assets.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Fixed Asset Register') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.depreciation.runs') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Depreciation Runs') }}</a>
                            <a href="{{ route('accounting.asset-usage.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Usage Log (UOP)') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.asset-disposals.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Disposals') }}</a>
                            <a href="{{ route('accounting.asset-transfers.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Transfers') }}</a>
                            <a href="{{ route('accounting.asset-impairments.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Impairments') }}</a>
                            <a href="{{ route('accounting.asset-revaluations.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Revaluations') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.employees.*') || request()->routeIs('accounting.payroll-runs.*') || request()->routeIs('accounting.paye-tables.*') || request()->routeIs('accounting.pension-schemes.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Payroll') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('accounting.employees.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Employees') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.payroll-runs.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Payroll Runs') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.paye-tables.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('PAYE Tax Tables') }}</a>
                            <a href="{{ route('accounting.pension-schemes.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Pension Schemes') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.income-statement.*') || request()->routeIs('accounting.balance-sheet.*') || request()->routeIs('accounting.cash-flow.*') || request()->routeIs('accounting.aging.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Reports') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('accounting.income-statement.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Income Statement') }}</a>
                            <a href="{{ route('accounting.balance-sheet.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Balance Sheet') }}</a>
                            <a href="{{ route('accounting.cash-flow.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Cash Flow Statement') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.aging.ar-summary') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('A/R Aging') }}</a>
                            <a href="{{ route('accounting.aging.ap-summary') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('A/P Aging') }}</a>
                        </div>
                    </div>

                    @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'analytics'))
                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('analytics.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Analytics') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('analytics.financial-ratios') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Financial Ratios') }}</a>
                            <a href="{{ route('analytics.revenue-expense-trends') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Revenue & Expense Trends') }}</a>
                            <a href="{{ route('analytics.sales') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Sales Analytics') }}</a>
                            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'purchasing'))
                            <a href="{{ route('analytics.purchasing') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Purchasing Analytics') }}</a>
                            @endif
                            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'inventory'))
                            <a href="{{ route('analytics.inventory') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Inventory Analytics') }}</a>
                            @endif
                            <a href="{{ route('analytics.profitability') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Profitability Analytics') }}</a>
                            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'budgets'))
                            <a href="{{ route('analytics.budget-vs-actual-trend') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Budget vs Actual Trend') }}</a>
                            @endif
                            <a href="{{ route('analytics.cash-flow-trend') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Cash Flow Trend') }}</a>
                        </div>
                    </div>
                    @endif

                    @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'bi'))
                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('bi.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('BI') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-56 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('bi.true-total-cost') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('True Total Cost per Branch') }}</a>
                            <a href="{{ route('bi.customer-lifetime-value') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Customer Lifetime Value') }}</a>
                            <a href="{{ route('bi.employee-productivity') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Employee Productivity') }}</a>
                            <a href="{{ route('bi.branch-profitability') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Branch Profitability') }}</a>
                        </div>
                    </div>
                    @endif

                    @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'pos'))
                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('pos.*') || request()->routeIs('pos.eis.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('POS') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Setup</div>
                            <a href="{{ route('pos.terminals.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Terminals') }}</a>
                            <a href="{{ route('pos.payment-methods.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Payment Methods') }}</a>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase mt-1">Sessions</div>
                            <a href="{{ route('pos.till-sessions.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Till Sessions') }}</a>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase mt-1">Sales</div>
                            <a href="{{ route('pos.sales.checkout') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Checkout') }}</a>
                            <a href="{{ route('pos.returns.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Returns / Refunds') }}</a>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase mt-1">Settlements</div>
                            <a href="{{ route('pos.settlements.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Payment Settlements') }}</a>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase mt-1">Reports</div>
                            <a href="{{ route('pos.reports.x-report') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('X-Report') }}</a>
                            <a href="{{ route('pos.reports.z-report') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Z-Report') }}</a>
                            <a href="{{ route('pos.reports.sales-by-terminal') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Sales by Terminal') }}</a>
                            <a href="{{ route('pos.reports.sales-by-cashier') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Sales by Cashier') }}</a>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase mt-1">E-Invoicing</div>
                            <a href="{{ route('pos.eis.terminals') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('EIS Terminals') }}</a>
                            <a href="{{ route('pos.eis.submissions') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('EIS Submissions') }}</a>
                        </div>
                    </div>
                    @endif

                    @if(Auth::user()->hasAnyRoleInCompany(['system_admin', 'company_admin']))
                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('system-settings.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Settings') }}
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute right-0 z-50 mt-1 w-56 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('system-settings.index', 'company') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Company Profile') }}</a>
                            <a href="{{ route('system-settings.index', 'regional') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Regional Settings') }}</a>
                            <a href="{{ route('system-settings.index', 'currency') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Currency Settings') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('system-settings.index', 'accounts') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Account Mappings') }}</a>
                            <a href="{{ route('system-settings.index', 'accounting') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Accounting Settings') }}</a>
                            <a href="{{ route('system-settings.index', 'approval') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Approval Settings') }}</a>
                            <a href="{{ route('system-settings.index', 'numbering') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Numbering') }}</a>
                            <a href="{{ route('system-settings.index', 'notifications') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Email & Notifications') }}</a>
                            <a href="{{ route('system-settings.index', 'branches') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Branches') }}</a>
                            <a href="{{ route('system-settings.index', 'fiscal-years') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Fiscal Years') }}</a>
                            <a href="{{ route('system-settings.index', 'data-hub') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Data Hub') }}</a>
                            <a href="{{ route('system-settings.index', 'import-export') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Import/Export') }}</a>
                            <a href="{{ route('system-settings.index', 'backups') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Backups') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('system-settings.audit-log') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Settings Audit Log') }}</a>
                        </div>
                    </div>
                    @endif

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('admin.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Admin') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute right-0 z-50 mt-1 w-56 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Setup</div>
                            <a href="{{ route('admin.setup-wizard.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Setup Wizard') }}</a>
                            <a href="{{ route('companies.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Companies') }}</a>
                            <a href="{{ route('branches.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Branches') }}</a>
                            <a href="{{ route('accounting.cost-centers.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Cost Centers') }}</a>
                            <a href="{{ route('accounting.fiscal-years.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Fiscal Years') }}</a>
                            <a href="{{ route('accounting.exchange-rates.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Exchange Rates') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Access</div>
                            <a href="{{ route('admin.users.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Users & Roles') }}</a>
                            <a href="{{ route('admin.security.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Security') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">System</div>
                            <a href="{{ route('admin.features.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Features') }}</a>
                            <a href="{{ route('admin.numbering-sequences.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Numbering Sequences') }}</a>
                            <a href="{{ route('admin.audit-log.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Audit Log') }}</a>
                            <a href="{{ route('admin.notifications.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Notifications') }}</a>
                            <a href="{{ route('admin.backups.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Backups') }}</a>
                            <a href="{{ route('admin.system-health.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('System Health') }}</a>
                            <a href="{{ route('admin.localization.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Localization') }}</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                @if(session('current_company_id') && isset($currentCompany))
                <div class="relative" x-data="{ companyOpen: false }" @click.away="companyOpen = false">
                    <button @click="companyOpen = !companyOpen" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">
                        <span class="text-gray-500 mr-1">Company:</span>
                        {{ $currentCompany->name }}
                        <svg class="ms-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="companyOpen" x-transition x-cloak class="absolute right-0 z-50 mt-1 w-56 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                        @foreach($userCompanies as $uc)
                        <a href="{{ route('companies.select', $uc->id) }}"
                           class="block px-4 py-2 text-sm {{ $uc->id === session('current_company_id') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                            {{ $uc->name }}
                            <span class="text-xs text-gray-400 ms-1">({{ $uc->pivot->role }})</span>
                        </a>
                        @endforeach
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="{{ route('companies.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">All Companies</a>
                    </div>
                </div>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if(session('current_company_id'))
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Sales & Purchases</div>
            <x-responsive-nav-link :href="route('accounting.customers.index')" :active="request()->routeIs('accounting.customers.*')">{{ __('Customers') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.quotations.index')" :active="request()->routeIs('accounting.quotations.*')">{{ __('Quotations') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.invoices.index')" :active="request()->routeIs('accounting.invoices.*')">{{ __('Invoices') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.sales-receipts.index')" :active="request()->routeIs('accounting.sales-receipts.*')">{{ __('Sales Receipts') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.credit-notes.index')" :active="request()->routeIs('accounting.credit-notes.*')">{{ __('Credit Notes') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.vendors.index')" :active="request()->routeIs('accounting.vendors.*')">{{ __('Vendors') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.purchase-requisitions.index')" :active="request()->routeIs('accounting.purchase-requisitions.*')">{{ __('Purchase Requisitions') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.purchase-orders.index')" :active="request()->routeIs('accounting.purchase-orders.*')">{{ __('Purchase Orders') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.goods-received-notes.index')" :active="request()->routeIs('accounting.goods-received-notes.*')">{{ __('Goods Received Notes') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.bills.index')" :active="request()->routeIs('accounting.bills.*')">{{ __('Bills') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.vendor-credits.index')" :active="request()->routeIs('accounting.vendor-credits.*')">{{ __('Vendor Credits') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.expenses.index')" :active="request()->routeIs('accounting.expenses.*')">{{ __('Expenses') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.vendor-centre.index')" :active="request()->routeIs('accounting.vendor-centre.*')">{{ __('Vendor Centre') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.products.index')" :active="request()->routeIs('accounting.products.*')">{{ __('Products') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Inventory</div>
            <x-responsive-nav-link :href="route('accounting.inventory-items.index')" :active="request()->routeIs('accounting.inventory-items.*')">{{ __('Inventory Items') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.item-categories.index')" :active="request()->routeIs('accounting.item-categories.*')">{{ __('Item Categories') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.stock-adjustments.index')" :active="request()->routeIs('accounting.stock-adjustments.*')">{{ __('Stock Adjustments') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.stock-transfers.index')" :active="request()->routeIs('accounting.stock-transfers.*')">{{ __('Stock Transfers') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.assemblies.index')" :active="request()->routeIs('accounting.assemblies.*')">{{ __('Assemblies') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.stock-counts.index')" :active="request()->routeIs('accounting.stock-counts.*')">{{ __('Stock Counts') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.uom-conversions.index')" :active="request()->routeIs('accounting.uom-conversions.*')">{{ __('UOM Conversions') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.landed-costs.index')" :active="request()->routeIs('accounting.landed-costs.*')">{{ __('Landed Cost') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.inventory-valuation.index')" :active="request()->routeIs('accounting.inventory-valuation.*')">{{ __('Valuation Report') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.low-stock.index')" :active="request()->routeIs('accounting.low-stock.*')">{{ __('Low Stock Report') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Payroll</div>
            <x-responsive-nav-link :href="route('accounting.employees.index')" :active="request()->routeIs('accounting.employees.*')">{{ __('Employees') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.payroll-runs.index')" :active="request()->routeIs('accounting.payroll-runs.*')">{{ __('Payroll Runs') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.paye-tables.index')" :active="request()->routeIs('accounting.paye-tables.*')">{{ __('PAYE Tax Tables') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.pension-schemes.index')" :active="request()->routeIs('accounting.pension-schemes.*')">{{ __('Pension Schemes') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Banking</div>
            <x-responsive-nav-link :href="route('accounting.bank-accounts.index')" :active="request()->routeIs('accounting.bank-accounts.*')">{{ __('Bank Accounts') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Accounting</div>
            <x-responsive-nav-link :href="route('accounting.accounts.index')" :active="request()->routeIs('accounting.accounts.*')">{{ __('Chart of Accounts') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.journal-entries.index')" :active="request()->routeIs('accounting.journal-entries.*')">{{ __('Journal Entries') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.general-ledger.index')" :active="request()->routeIs('accounting.general-ledger.*')">{{ __('General Ledger') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.trial-balance.index')" :active="request()->routeIs('accounting.trial-balance.*')">{{ __('Trial Balance') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.periods.index')" :active="request()->routeIs('accounting.periods.*')">{{ __('Periods') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.fiscal-years.index')" :active="request()->routeIs('accounting.fiscal-years.*')">{{ __('Fiscal Years') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.cost-centers.index')" :active="request()->routeIs('accounting.cost-centers.*')">{{ __('Cost Centers') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.budgets.index')" :active="request()->routeIs('accounting.budgets.*')">{{ __('Budgets') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.account-classification.index')" :active="request()->routeIs('accounting.account-classification.*')">{{ __('Account Classification') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Fixed Assets</div>
            <x-responsive-nav-link :href="route('accounting.asset-categories.index')" :active="request()->routeIs('accounting.asset-categories.*')">{{ __('Asset Categories') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.fixed-assets.index')" :active="request()->routeIs('accounting.fixed-assets.*')">{{ __('Fixed Asset Register') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.depreciation.runs')" :active="request()->routeIs('accounting.depreciation.*')">{{ __('Depreciation Runs') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.asset-disposals.index')" :active="request()->routeIs('accounting.asset-disposals.*')">{{ __('Disposals') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.asset-impairments.index')" :active="request()->routeIs('accounting.asset-impairments.*')">{{ __('Impairments') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.asset-revaluations.index')" :active="request()->routeIs('accounting.asset-revaluations.*')">{{ __('Revaluations') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Reports</div>
            <x-responsive-nav-link :href="route('accounting.income-statement.index')" :active="request()->routeIs('accounting.income-statement.*')">{{ __('Income Statement') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.balance-sheet.index')" :active="request()->routeIs('accounting.balance-sheet.*')">{{ __('Balance Sheet') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.cash-flow.index')" :active="request()->routeIs('accounting.cash-flow.*')">{{ __('Cash Flow') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.aging.ar-summary')" :active="request()->routeIs('accounting.aging.*')">{{ __('A/R Aging') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.aging.ap-summary')" :active="request()->routeIs('accounting.aging.*')">{{ __('A/P Aging') }}</x-responsive-nav-link>
            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'analytics'))
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Analytics</div>
            <x-responsive-nav-link :href="route('analytics.financial-ratios')" :active="request()->routeIs('analytics.financial-ratios')">{{ __('Financial Ratios') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('analytics.revenue-expense-trends')" :active="request()->routeIs('analytics.revenue-expense-trends')">{{ __('Revenue & Expense Trends') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('analytics.sales')" :active="request()->routeIs('analytics.sales')">{{ __('Sales Analytics') }}</x-responsive-nav-link>
            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'purchasing'))
            <x-responsive-nav-link :href="route('analytics.purchasing')" :active="request()->routeIs('analytics.purchasing')">{{ __('Purchasing Analytics') }}</x-responsive-nav-link>
            @endif
            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'inventory'))
            <x-responsive-nav-link :href="route('analytics.inventory')" :active="request()->routeIs('analytics.inventory')">{{ __('Inventory Analytics') }}</x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('analytics.profitability')" :active="request()->routeIs('analytics.profitability')">{{ __('Profitability Analytics') }}</x-responsive-nav-link>
            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'budgets'))
            <x-responsive-nav-link :href="route('analytics.budget-vs-actual-trend')" :active="request()->routeIs('analytics.budget-vs-actual-trend')">{{ __('Budget vs Actual') }}</x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('analytics.cash-flow-trend')" :active="request()->routeIs('analytics.cash-flow-trend')">{{ __('Cash Flow Trend') }}</x-responsive-nav-link>
            @endif
            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'bi'))
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Business Intelligence</div>
            <x-responsive-nav-link :href="route('bi.true-total-cost')" :active="request()->routeIs('bi.true-total-cost')">{{ __('True Total Cost') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('bi.customer-lifetime-value')" :active="request()->routeIs('bi.customer-lifetime-value')">{{ __('Customer Lifetime Value') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('bi.employee-productivity')" :active="request()->routeIs('bi.employee-productivity')">{{ __('Employee Productivity') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('bi.branch-profitability')" :active="request()->routeIs('bi.branch-profitability')">{{ __('Branch Profitability') }}</x-responsive-nav-link>
            @endif
            @if(\App\Services\FeatureManagement::isEnabled(session('current_company_id') ?? 0, 'pos'))
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">POS</div>
            <x-responsive-nav-link :href="route('pos.terminals.index')" :active="request()->routeIs('pos.terminals.*')">{{ __('Terminals') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.payment-methods.index')" :active="request()->routeIs('pos.payment-methods.*')">{{ __('Payment Methods') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.till-sessions.index')" :active="request()->routeIs('pos.till-sessions.*')">{{ __('Till Sessions') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.returns.index')" :active="request()->routeIs('pos.returns.*')">{{ __('Returns / Refunds') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.sales.checkout')" :active="request()->routeIs('pos.sales.*')">{{ __('Checkout') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.settlements.index')" :active="request()->routeIs('pos.settlements.*')">{{ __('Payment Settlements') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Reports</div>
            <x-responsive-nav-link :href="route('pos.reports.x-report')" :active="request()->routeIs('pos.reports.x-report')">{{ __('X-Report') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.reports.z-report')" :active="request()->routeIs('pos.reports.z-report')">{{ __('Z-Report') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.reports.sales-by-terminal')" :active="request()->routeIs('pos.reports.sales-by-terminal')">{{ __('Sales by Terminal') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.reports.sales-by-cashier')" :active="request()->routeIs('pos.reports.sales-by-cashier')">{{ __('Sales by Cashier') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.eis.terminals')" :active="request()->routeIs('pos.eis.*')">{{ __('EIS Terminals') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.eis.submissions')" :active="request()->routeIs('pos.eis.submissions*')">{{ __('EIS Submissions') }}</x-responsive-nav-link>
            @endif
            @if(Auth::user()->hasAnyRoleInCompany(['system_admin', 'company_admin']))
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">System Settings</div>
            <x-responsive-nav-link :href="route('system-settings.index', 'company')" :active="request()->routeIs('system-settings.*')">{{ __('Company Profile') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'regional')" :active="request()->routeIs('system-settings.*')">{{ __('Regional Settings') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'currency')" :active="request()->routeIs('system-settings.*')">{{ __('Currency Settings') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'accounting')" :active="request()->routeIs('system-settings.*')">{{ __('Accounting Settings') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'approval')" :active="request()->routeIs('system-settings.*')">{{ __('Approval Settings') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'numbering')" :active="request()->routeIs('system-settings.*')">{{ __('Numbering') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'notifications')" :active="request()->routeIs('system-settings.*')">{{ __('Email & Notifications') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'branches')" :active="request()->routeIs('system-settings.*')">{{ __('Branches') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'fiscal-years')" :active="request()->routeIs('system-settings.*')">{{ __('Fiscal Years') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'data-hub')" :active="request()->routeIs('system-settings.*')">{{ __('Data Hub') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'import-export')" :active="request()->routeIs('system-settings.*')">{{ __('Import/Export') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.index', 'backups')" :active="request()->routeIs('system-settings.*')">{{ __('Backups') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('system-settings.audit-log')" :active="request()->routeIs('system-settings.audit-log')">{{ __('Settings Audit Log') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Admin</div>
            <x-responsive-nav-link :href="route('admin.setup-wizard.index')" :active="request()->routeIs('admin.setup-wizard.*')">{{ __('Setup Wizard') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">{{ __('Users & Roles') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.security.index')" :active="request()->routeIs('admin.security.*')">{{ __('Security') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.features.index')" :active="request()->routeIs('admin.features.*')">{{ __('Features') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.numbering-sequences.index')" :active="request()->routeIs('admin.numbering-sequences.*')">{{ __('Numbering Sequences') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.audit-log.index')" :active="request()->routeIs('admin.audit-log.*')">{{ __('Audit Log') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.notifications.index')" :active="request()->routeIs('admin.notifications.*')">{{ __('Notifications') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.backups.index')" :active="request()->routeIs('admin.backups.*')">{{ __('Backups') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.system-health.index')" :active="request()->routeIs('admin.system-health.*')">{{ __('System Health') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.localization.index')" :active="request()->routeIs('admin.localization.*')">{{ __('Localization') }}</x-responsive-nav-link>
            @endif
            @endif
        </div>
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
