<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Reports') }}</h1>
                <p class="sub">{{ __('Detailed tax reporting across the ledger.') }}</p>
            </div>
        </div>

        @php
            $reportIcons = [
                'default' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8',
                'income_statement' => 'M23 6l-9.5 9.5-5-5L1 18M17 6h6v6',
                'balance_sheet' => 'M12 3v18M3 7h18M6 7l-3 5 3 5m12-10l3 5-3 5M7 21h10',
                'cash_flow' => 'M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3',
                'trial_balance' => 'M12 3v18M3 7h18M6 7l-3 5 3 5m12-10l3 5-3 5M7 21h10',
                'general_ledger' => 'M19 17V5a2 2 0 00-2-2H4M8 21h12a2 2 0 002-2v-1a1 1 0 00-1-1H10a1 1 0 00-1 1v1a2 2 0 11-4 0V5a2 2 0 10-4 0v2M8 21a2 2 0 01-2-2',
                'reconciliation' => 'M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11',
            ];
        @endphp

        @if ($reports->isEmpty())
            <div class="tx-card">
                <div class="tx-pad" style="text-align:center;padding:48px 24px;">
                    <p style="color:var(--sub, #41585c);font-size:13.5px;">{{ __('No tax reports are available for your permissions yet.') }}</p>
                </div>
            </div>
        @else
            <div class="tx-tiles">
                @foreach ($reports as $report)
                    @php
                        $iconPath = $reportIcons[$report['key']] ?? $reportIcons['default'];
                    @endphp
                    <a href="{{ $report['url'] }}" class="tx-tile" title="{{ $report['description'] }}">
                        <span class="ic">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $iconPath }}"/></svg>
                        </span>
                        <span>{{ $report['name'] }}</span>
                        <em>&rarr;</em>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
