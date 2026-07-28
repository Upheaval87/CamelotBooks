<div class="space-y-6">
    {{-- Export Hub --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Report Exports</h3>
            <p class="mt-1 text-sm text-gray-600">Quick access to all available report exports. Each report can be downloaded as CSV or PDF.</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                        ['label' => 'AR Aging Summary', 'csv' => route('accounting.aging.ar-summary.export-csv'), 'pdf' => null],
                        ['label' => 'AP Aging Summary', 'csv' => route('accounting.aging.ap-summary.export-csv'), 'pdf' => null],
                        ['label' => 'Audit Log', 'csv' => route('admin.audit-log.export-csv'), 'pdf' => null],
                    ];
                @endphp
                @foreach($exports as $export)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-sm font-medium text-gray-700">{{ $export['label'] }}</span>
                        <div class="flex items-center gap-2">
                            <a href="{{ $export['csv'] }}" class="inline-flex items-center px-2 py-1 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
                                CSV
                            </a>
                            @if($export['pdf'])
                                <a href="{{ $export['pdf'] }}" class="inline-flex items-center px-2 py-1 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
                                    PDF
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Master Data Import --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Master Data Import</h3>
            <p class="mt-1 text-sm text-gray-600">Import your business data from CSV files. Download a template, fill in your data, then upload.</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @php
                    $imports = [
                        ['type' => 'customers', 'label' => 'Customers', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['type' => 'vendors', 'label' => 'Vendors', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['type' => 'products', 'label' => 'Products & Services', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['type' => 'accounts', 'label' => 'Chart of Accounts', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ];
                @endphp
                @foreach($imports as $import)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $import['icon'] }}" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">{{ $import['label'] }}</h4>
                                <p class="text-xs text-gray-500">CSV format</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">Template download and CSV import coming soon.</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
