<x-app-layout>
    <x-list-header title="{{ __('POS Terminal') }} — {{ session('pos_terminal_identifier') }}" />

    <div class="flex justify-end mb-4 px-4 sm:px-0 gap-3">
        <span class="text-sm text-gray-600 self-center">Cashier: <strong>{{ session('pos_cashier_name') }}</strong></span>
        <form method="POST" action="{{ route('pos.cashier.logout') }}" class="inline">
            @csrf
            <x-button variant="ghost" type="submit" class="text-red-600 hover:text-red-800">End Session</x-button>
        </form>
    </div>

    @php
        $companyId = session('current_company_id');
        $terminalId = session('pos_terminal_id');
        $cashierId = session('pos_cashier_id');

        $openSession = \App\Models\PosCashierSession::where('company_id', $companyId)
            ->where('terminal_id', $terminalId)
            ->where('user_id', $cashierId)
            ->where('status', 'open')
            ->first();

        $todaySalesCount = 0;
        $todaySalesTotal = 0;
        if ($openSession) {
            $todaySalesCount = \App\Models\PosSale::where('cashier_session_id', $openSession->id)
                ->where('status', 'posted')
                ->count();
            $todaySalesTotal = \App\Models\PosSale::where('cashier_session_id', $openSession->id)
                ->where('status', 'posted')
                ->sum('total');
        }

        $terminal = \App\Models\PosTerminal::find($terminalId);
    @endphp

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Till Status Card --}}
            <div class="card p-6">
                @if($openSession)
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="form-section-label">Till Open</div>
                            <p class="text-sm text-ink-soft mt-1">
                                Opened at {{ $openSession->opened_at?->format('g:i A') }} ·
                                Float: @money($openSession->opening_float)
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <x-button variant="ghost" href="{{ route('pos.till-sessions.show', $openSession->id) }}">Session Details</x-button>
                            <x-button variant="primary" href="{{ route('pos.sales.checkout') }}">Open Cash Register</x-button>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <div class="form-section-label">No Open Till</div>
                        <p class="text-sm text-ink-soft mb-4">Open a till session to start making sales.</p>
                        <x-button variant="primary" href="{{ route('pos.till-sessions.index') }}">Open Till Session</x-button>
                    </div>
                @endif
            </div>

            {{-- Today's Summary --}}
            @if($openSession)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="card p-6">
                        <p class="text-sm font-medium text-ink-soft">Sales Today</p>
                        <p class="mt-1 text-3xl font-bold text-gray-900">{{ $todaySalesCount }}</p>
                    </div>
                    <div class="card p-6">
                        <p class="text-sm font-medium text-ink-soft">Revenue Today</p>
                        <p class="mt-1 text-3xl font-bold text-gray-900">@money($todaySalesTotal)</p>
                    </div>
                    <div class="card p-6">
                        <p class="text-sm font-medium text-ink-soft">Expected in Drawer</p>
                        @php
                            $expectedCash = (float) $openSession->opening_float + (float) $todaySalesTotal;
                        @endphp
                        <p class="mt-1 text-3xl font-bold text-gray-900">@money($expectedCash)</p>
                    </div>
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('pos.sales.checkout') }}"
                    class="block card p-6 hover:shadow-md transition">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800">Checkout</h4>
                            <p class="text-sm text-ink-soft">Make a new sale</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('pos.till-sessions.index') }}"
                    class="block card p-6 hover:shadow-md transition">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800">Till Sessions</h4>
                            <p class="text-sm text-ink-soft">Open, close, and view till sessions</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('pos.settlements.index') }}"
                    class="block card p-6 hover:shadow-md transition">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800">Settlements</h4>
                            <p class="text-sm text-ink-soft">Record card/mobile money settlements</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('accounting.journal-entries.index') }}"
                    class="block card p-6 hover:shadow-md transition">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800">Journal Entries</h4>
                            <p class="text-sm text-ink-soft">View posted POS journal entries</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
