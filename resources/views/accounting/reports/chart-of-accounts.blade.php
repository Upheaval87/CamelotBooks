<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Chart of Accounts') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-toolbar class="mb-6">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider mr-1">Record</span>
                <a href="{{ url()->current() }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </a>
                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-atlas-navy/20 text-atlas-navy text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save Preset
                </button>

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider mr-1">Document</span>
                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export CSV
                </button>
                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Export PDF
                </button>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <x-dropdown align="left" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center justify-center w-7 h-7 bg-transparent text-atlas-navy/50 rounded-md hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Search Account
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                Account History
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Merge Accounts
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                Renumber
                            </button>
                        </div>
                    </x-slot>
                </x-dropdown>

                <x-slot name="right">
                    <button type="button" onclick="window.feedback.openConfirm({ title: 'Deactivate this account? It will be hidden from new entries.' })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent border border-atlas-danger/30 text-atlas-danger text-sm font-medium rounded-md hover:bg-atlas-danger/5 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Deactivate Account
                    </button>
                </x-slot>
            </x-toolbar>

            <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" class="flex items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account Type</label>
                        <select name="type" class="mt-1 block border-gray-300 rounded-md shadow-sm focus:ring-gold-500 focus:border-gold-500 sm:text-sm">
                            <option value="">All Types</option>
                            @foreach(['asset','liability','equity','income','expense'] as $t)
                                <option value="{{ $t }}" {{ ($selected_type ?? '') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button type="submit">Filter</x-primary-button>
                    <span class="text-sm text-gray-500">{{ $total_accounts }} accounts</span>
                </form>
            </div>
            @foreach($grouped as $group)
            <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">{{ ucfirst($group['type']) }} Accounts</h2>
                </div>
                <table class="datasheet">
                    <thead><tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Sub Type</th>
                        <th>Description</th>
                        <th class="text-right">Opening ({{ $cs }})</th>
                        <th class="text-right">Current ({{ $cs }})</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($group['accounts'] as $a)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm font-sans">{{ $a['code'] }}</td>
                            <td>{{ $a['name'] }}</td>
                            <td class="text-ink-soft">{{ $a['sub_type'] ?? '—' }}</td>
                            <td class="text-ink-soft">{{ $a['description'] ?? '—' }}</td>
                            <td class="numeric">{{ format_number($a['opening_balance']) }}</td>
                            <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($a['current_balance']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
