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
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.customers.*') || request()->routeIs('accounting.vendors.*') || request()->routeIs('accounting.invoices.*') || request()->routeIs('accounting.bills.*') || request()->routeIs('accounting.products.*') || request()->routeIs('accounting.credit-notes.*') || request()->routeIs('accounting.vendor-credits.*') || request()->routeIs('accounting.customer-payments.*') || request()->routeIs('accounting.vendor-payments.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Sales & Purchases') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Sales</div>
                            <a href="{{ route('accounting.customers.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Customers') }}</a>
                            <a href="{{ route('accounting.invoices.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Invoices') }}</a>
                            <a href="{{ route('accounting.credit-notes.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Credit Notes') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <div class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">Purchases</div>
                            <a href="{{ route('accounting.vendors.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Vendors') }}</a>
                            <a href="{{ route('accounting.bills.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Bills') }}</a>
                            <a href="{{ route('accounting.vendor-credits.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Vendor Credits') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.products.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Products & Services') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.inventory-items.*') || request()->routeIs('accounting.stock-adjustments.*') || request()->routeIs('accounting.stock-transfers.*') || request()->routeIs('accounting.inventory-valuation.*') || request()->routeIs('accounting.low-stock.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Inventory') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity5">
                            <a href="{{ route('accounting.inventory-items.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Inventory Items') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.stock-adjustments.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Stock Adjustments') }}</a>
                            <a href="{{ route('accounting.stock-transfers.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Stock Transfers') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.inventory-valuation.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Valuation Report') }}</a>
                            <a href="{{ route('accounting.low-stock.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Low Stock Report') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.bank-accounts.*') || request()->routeIs('accounting.bank-reconciliation.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ __('Banking') }}
                            <svg class="ms-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="ddOpen" x-transition x-cloak class="absolute z-50 mt-1 w-52 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5">
                            <a href="{{ route('accounting.bank-accounts.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Bank Accounts') }}</a>
                            <a href="{{ route('accounting.bank-accounts.transfer-form') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Transfer Funds') }}</a>
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.accounts.*') || request()->routeIs('accounting.journal-entries.*') || request()->routeIs('accounting.general-ledger.*') || request()->routeIs('accounting.trial-balance.*') || request()->routeIs('accounting.periods.*') || request()->routeIs('accounting.fiscal-years.*') || request()->routeIs('accounting.recurring-journals.*') || request()->routeIs('accounting.cost-centers.*') || request()->routeIs('accounting.exchange-rates.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
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
                        </div>
                    </div>

                    <div class="relative" x-data="{ ddOpen: false }" @click.away="ddOpen = false">
                        <button @click="ddOpen = !ddOpen" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition {{ request()->routeIs('accounting.income-statement.*') || request()->routeIs('accounting.balance-sheet.*') || request()->routeIs('accounting.cash-flow.*') || request()->routeIs('accounting.aging.*') || request()->routeIs('accounting.account-classification.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
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
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('accounting.account-classification.index') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Account Classification') }}</a>
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
            <x-responsive-nav-link :href="route('accounting.invoices.index')" :active="request()->routeIs('accounting.invoices.*')">{{ __('Invoices') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.credit-notes.index')" :active="request()->routeIs('accounting.credit-notes.*')">{{ __('Credit Notes') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.vendors.index')" :active="request()->routeIs('accounting.vendors.*')">{{ __('Vendors') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.bills.index')" :active="request()->routeIs('accounting.bills.*')">{{ __('Bills') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.vendor-credits.index')" :active="request()->routeIs('accounting.vendor-credits.*')">{{ __('Vendor Credits') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.products.index')" :active="request()->routeIs('accounting.products.*')">{{ __('Products') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Inventory</div>
            <x-responsive-nav-link :href="route('accounting.inventory-items.index')" :active="request()->routeIs('accounting.inventory-items.*')">{{ __('Inventory Items') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.stock-adjustments.index')" :active="request()->routeIs('accounting.stock-adjustments.*')">{{ __('Stock Adjustments') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.stock-transfers.index')" :active="request()->routeIs('accounting.stock-transfers.*')">{{ __('Stock Transfers') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.inventory-valuation.index')" :active="request()->routeIs('accounting.inventory-valuation.*')">{{ __('Valuation Report') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.low-stock.index')" :active="request()->routeIs('accounting.low-stock.*')">{{ __('Low Stock Report') }}</x-responsive-nav-link>
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Payroll</div>
            <x-responsive-nav-link :href="route('accounting.employees.index')" :active="request()->routeIs('accounting.employees.*')">{{ __('Employees') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.payroll-runs.index')" :active="request()->routeIs('accounting.payroll-runs.*')">{{ __('Payroll Runs') }}</x-responsive-nav-link>
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
            <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase">Reports</div>
            <x-responsive-nav-link :href="route('accounting.income-statement.index')" :active="request()->routeIs('accounting.income-statement.*')">{{ __('Income Statement') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.balance-sheet.index')" :active="request()->routeIs('accounting.balance-sheet.*')">{{ __('Balance Sheet') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.cash-flow.index')" :active="request()->routeIs('accounting.cash-flow.*')">{{ __('Cash Flow') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.aging.ar-summary')" :active="request()->routeIs('accounting.aging.*')">{{ __('A/R Aging') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounting.aging.ap-summary')" :active="request()->routeIs('accounting.aging.*')">{{ __('A/P Aging') }}</x-responsive-nav-link>
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
