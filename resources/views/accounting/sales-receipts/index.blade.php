<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Sales Receipt') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.sales-receipts.create') }}">
                    {{ __('Create Sales Receipt') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.sales-receipts.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Customer, number..." />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>Voided</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('accounting.sales-receipts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th class="text-right">Total ({{ $cs }})</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receipts as $receipt)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.sales-receipts.show', $receipt) }}" class="text-ink hover:text-gold">{{ $receipt->receipt_number }}</a>
                                    </td>
                                    <td>{{ $receipt->customer->name ?? 'Walk-in' }}</td>
                                    <td class="text-ink-soft">{{ $receipt->receipt_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="numeric">{{ format_number($receipt->total) }}</td>
                                    <td class="text-center">
                                        @switch($receipt->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('posted')
                                                <span class="status-pill positive">Posted</span>
                                                @break
                                            @case('voided')
                                                <span class="status-pill negative">Voided</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.sales-receipts.show', $receipt) }}" class="text-ink hover:text-gold">View</a>
                                        @if($receipt->status === 'draft')
                                            <form method="POST" action="{{ route('accounting.sales-receipts.post', $receipt) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">Post</button>
                                            </form>
                                        @endif
                                        @if($receipt->status === 'posted')
                                            <form method="POST" action="{{ route('accounting.sales-receipts.void', $receipt) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Void this receipt?')">Void</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">No sales receipts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($receipts->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $receipts->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
