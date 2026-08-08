<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Create Quotation') }}" />
    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.quotations.create') }}">
                    {{ __('Create Quotation') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.quotations.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <div class="scoped-search-field mt-1">
                            <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Customer, number..." autocomplete="off" />
                            <span class="scoped-search-divider" aria-hidden="true"></span>
                            <button type="button" class="scoped-search-open" title="{{ __('Search this list') }}" onclick="window.dispatchEvent(new CustomEvent('open-global-search', { detail: { entity: 'quotation' } }))">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="declined" {{ request('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                            <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('accounting.quotations.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 shadow-sm hover:bg-gray-50">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>
            </div>
            
            
            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Quotation #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Valid Until</th>
                                <th class="text-right">Total ({{ $cs }})</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotations as $q)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.quotations.show', $q) }}" class="text-ink hover:text-gold">{{ $q->quotation_number }}</a>
                                    </td>
                                    <td>{{ $q->customer->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $q->quotation_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $q->valid_until?->format('M d, Y') ?? '—' }}</td>
                                    <td class="numeric">{{ format_number($q->total) }}</td>
                                    <td class="text-center">
                                        @switch($q->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('sent')
                                                <span class="status-pill neutral">Sent</span>
                                                @break
                                            @case('accepted')
                                                <span class="status-pill positive">Accepted</span>
                                                @break
                                            @case('declined')
                                                <span class="status-pill negative">Declined</span>
                                                @break
                                            @case('converted')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-mint-100 text-green-700">Converted</span>
                                                @break
                                            @case('void')
                                                <span class="status-pill neutral">Void</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.quotations.show', $q) }}" class="text-ink hover:text-gold">View</a>
                                        @if($q->status === 'draft')
                                            <a href="{{ route('accounting.quotations.edit', $q) }}" class="text-ink hover:text-gold">Edit</a>
                                            <form method="POST" action="{{ route('accounting.quotations.send', $q) }}" class="inline">@csrf<button type="submit" class="text-gold-700 hover:text-gold-800">Send</button></form>
                                        @endif
                                        @if(in_array($q->status, ['draft', 'sent', 'accepted']))
                                            @can('quotations.void')
                                                <form method="POST" action="{{ route('accounting.quotations.void', $q) }}" class="inline">@csrf<button type="submit" class="text-red-600 hover:text-red-900" onclick="return fbConfirmButton(event, 'Void this quotation?', { type: 'danger' })">Void</button></form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-ink-soft">No quotations found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($quotations->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $quotations->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
