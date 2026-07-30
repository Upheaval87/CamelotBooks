<div class="settings-section-header">
    <div class="settings-section-eyebrow">09 · DATA HUB</div>
    <div class="settings-section-title">Report Exports</div>
    <p class="settings-section-desc">Quick access to all available report exports. Each report can be downloaded as CSV or PDF.</p>
    <hr class="settings-section-divider">
</div>

<div class="settings-card">
    @php
        $exports = [
            ['label' => 'General Ledger', 'csv' => route('accounting.general-ledger.export-csv'), 'pdf' => null],
            ['label' => 'Trial Balance', 'csv' => route('accounting.trial-balance.export-csv'), 'pdf' => route('accounting.trial-balance.export-pdf')],
            ['label' => 'Income Statement', 'csv' => route('accounting.income-statement.export-csv'), 'pdf' => route('accounting.income-statement.export-pdf')],
            ['label' => 'Balance Sheet', 'csv' => route('accounting.balance-sheet.export-csv'), 'pdf' => route('accounting.balance-sheet.export-pdf')],
            ['label' => 'Statement of Changes in Equity', 'csv' => route('accounting.equity-statement.export-csv'), 'pdf' => route('accounting.equity-statement.export-pdf')],
            ['label' => 'Cash Flow Statement', 'csv' => route('accounting.cash-flow.export-csv'), 'pdf' => route('accounting.cash-flow.export-pdf')],
            ['label' => 'Inventory Valuation', 'csv' => route('accounting.inventory-valuation.export-csv'), 'pdf' => route('accounting.inventory-valuation.export-pdf')],
            ['label' => 'Low Stock Report', 'csv' => route('accounting.low-stock.export-csv'), 'pdf' => null],
            ['label' => 'AR Aging Summary', 'csv' => route('accounting.aging.export-csv', ['type' => 'ar']), 'pdf' => null],
            ['label' => 'AP Aging Summary', 'csv' => route('accounting.aging.export-csv', ['type' => 'ap']), 'pdf' => null],
            ['label' => 'Audit Log', 'csv' => route('admin.audit-log.export-csv'), 'pdf' => null],
        ];
    @endphp
    @foreach($exports as $export)
        <div class="settings-compact-row">
            <span class="settings-compact-row-label">{{ $export['label'] }}</span>
            <div class="flex items-center gap-2">
                <a href="{{ $export['csv'] }}" class="settings-pill-btn">CSV</a>
                @if($export['pdf'])
                    <a href="{{ $export['pdf'] }}" class="settings-pill-btn">PDF</a>
                @endif
            </div>
        </div>
    @endforeach
</div>

<div class="settings-section-header mt-8">
    <div class="settings-section-eyebrow">10 · MASTER DATA IMPORT</div>
    <div class="settings-section-title">Master Data Import</div>
    <p class="settings-section-desc">Import your business data from CSV files. Download a template, fill in your data, then upload.</p>
    <hr class="settings-section-divider">
</div>

<div class="settings-card">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @php
            $imports = [
                ['type' => 'customers', 'label' => 'Customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['type' => 'vendors', 'label' => 'Vendors', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                ['type' => 'products', 'label' => 'Products & Services', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['type' => 'accounts', 'label' => 'Chart of Accounts', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
            ];
        @endphp
        @foreach($imports as $import)
            <div class="flex items-center gap-3 p-4 border border-line rounded-lg">
                <div class="settings-icon-square">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $import['icon'] }}" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-ink">{{ $import['label'] }}</h4>
                    <p class="text-xs text-ink-faint">CSV format</p>
                    <a href="#" class="text-xs text-gold hover:underline">Download Template</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
