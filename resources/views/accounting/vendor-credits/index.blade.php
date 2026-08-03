<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Vendor Credit') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.vendor-credits.create') }}">
                    {{ __('Create Vendor Credit') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.vendor-credits.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <div class="scoped-search-field mt-1">
                            <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Vendor name..." autocomplete="off" />
                            <span class="scoped-search-divider" aria-hidden="true"></span>
                            <button type="button" class="scoped-search-open" title="{{ __('Search across all records') }}" onclick="window.dispatchEvent(new CustomEvent('open-global-search'))">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="applied" {{ request('status') === 'applied' ? 'selected' : '' }}>Applied</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('accounting.vendor-credits.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Credit #</th>
                                <th>Vendor</th>
                                <th>Date</th>
                                <th>Reference Bill</th>
                                <th class="text-right">Amount ({{ $cs }})</th>
                                <th class="text-right">Applied ({{ $cs }})</th>
                                <th class="text-right">Available ({{ $cs }})</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendorCredits as $vendorCredit)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.vendor-credits.show', $vendorCredit) }}" class="text-ink hover:text-gold">
                                            {{ $vendorCredit->vendor_credit_number }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $vendorCredit->vendor->name ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $vendorCredit->credit_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $vendorCredit->bill->bill_number ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($vendorCredit->total) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($vendorCredit->amount_applied) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($vendorCredit->available) }}
                                    </td>
                                    <td class="text-center">
                                        @switch($vendorCredit->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('issued')
                                                <span class="status-pill neutral">Issued</span>
                                                @break
                                            @case('applied')
                                                <span class="status-pill positive">Applied</span>
                                                @break
                                            @case('void')
                                                <span class="status-pill neutral">Void</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.vendor-credits.show', $vendorCredit) }}" class="text-ink hover:text-gold">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-ink-soft">
                                        No vendor credits found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($vendorCredits->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $vendorCredits->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
