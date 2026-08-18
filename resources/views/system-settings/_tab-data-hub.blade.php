<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'data-hub'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <a href="{{ route('accounting.report-center.index') }}" class="btn ghost">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                {{ __('Open Report Center') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-sec">
        <div class="sec-head">
            <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg></span>
            <h2>{{ __('Report Exports') }}</h2>
            <div class="rule"></div>
        </div>
        <p class="sub">Quick access to all available report exports. Each report can be downloaded as CSV or PDF.</p>

        @php
            $exports = [
                ['label' => 'General Ledger', 'csv' => route('accounting.general-ledger.export-csv'), 'pdf' => null],
                ['label' => 'Trial Balance', 'csv' => route('accounting.trial-balance.export-csv'), 'pdf' => route('accounting.trial-balance.export-pdf')],
                ['label' => 'Income Statement', 'csv' => route('accounting.income-statement.export-csv'), 'pdf' => route('accounting.income-statement.export-pdf')],
                ['label' => 'Balance Sheet', 'csv' => route('accounting.balance-sheet.export-csv'), 'pdf' => route('accounting.balance-sheet.export-pdf')],
                ['label' => 'Statement of Changes in Equity', 'csv' => route('accounting.equity-statement.export-csv'), 'pdf' => route('accounting.equity-statement.export-pdf')],
                ['label' => 'Cash Flow Statement', 'csv' => route('accounting.cash-flow.export-csv'), 'pdf' => route('accounting.cash-flow.export-pdf')],
                ['label' => 'Inventory Valuation', 'csv' => Route::has('accounting.invsetup.valuation') ? route('accounting.invsetup.valuation') : '#', 'pdf' => null],
                ['label' => 'Low Stock Report', 'csv' => Route::has('accounting.invsetup.lowstock') ? route('accounting.invsetup.lowstock') : '#', 'pdf' => null],
                ['label' => 'AR Aging Summary', 'csv' => route('accounting.aging.export-csv', ['type' => 'ar']), 'pdf' => null],
                ['label' => 'AP Aging Summary', 'csv' => route('accounting.aging.export-csv', ['type' => 'ap']), 'pdf' => null],
                ['label' => 'Audit Log', 'csv' => route('admin.audit-log.export-csv'), 'pdf' => null],
            ];
        @endphp
        @foreach($exports as $export)
            <div class="e-row">
                <span class="lbl">{{ $export['label'] }}</span>
                <div class="tbtns">
                    <a href="{{ $export['csv'] }}" class="btn ghost sm">CSV</a>
                    @if($export['pdf'])
                        <a href="{{ $export['pdf'] }}" class="btn ghost sm">PDF</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-sec">
        <div class="sec-head">
            <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg></span>
            <h2>{{ __('Master Data Import') }}</h2>
            <div class="rule"></div>
        </div>
        <p class="sub">Import your business data from CSV files. Templates for each data set are available from the corresponding module.</p>

        <div class="import-grid">
            @php
                $imports = [
                    ['type' => 'customers', 'label' => 'Customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['type' => 'vendors', 'label' => 'Vendors', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['type' => 'products', 'label' => 'Products & Services', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['type' => 'accounts', 'label' => 'Chart of Accounts', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                ];
            @endphp
            @foreach($imports as $import)
                <div class="import-card">
                    <span class="ic">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $import['icon'] }}" />
                        </svg>
                    </span>
                    <div>
                        <h4>{{ $import['label'] }}</h4>
                        <p class="em">CSV format</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
