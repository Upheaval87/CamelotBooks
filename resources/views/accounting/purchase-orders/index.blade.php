<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Create Purchase Order') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.purchase-orders.create') }}">
                    {{ __('Create Purchase Order') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.purchase-orders.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="partially_received" {{ request('status') === 'partially_received' ? 'selected' : '' }}>Partially Received</option>
                            <option value="fully_received" {{ request('status') === 'fully_received' ? 'selected' : '' }}>Fully Received</option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('status'))
                            <a href="{{ route('accounting.purchase-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th class="text-right">Total ({{ $cs }})</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php $total = $order->lines->sum('amount'); @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.purchase-orders.show', $order) }}" class="text-ink hover:text-gold">
                                            {{ $order->po_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">{{ $order->date?->format('M d, Y') ?? '—' }}</td>
                                    <td>{{ $order->vendor->name ?? '—' }}</td>
                                    <td class="numeric">{{ format_number($total) }}</td>
                                    <td class="text-center">
                                        @switch($order->status)
                                            @case('draft')
                                                <span class="status-pill neutral">Draft</span>
                                                @break
                                            @case('sent')
                                                <span class="status-pill neutral">Sent</span>
                                                @break
                                            @case('partially_received')
                                                <span class="status-pill neutral">Partial</span>
                                                @break
                                            @case('fully_received')
                                                <span class="status-pill positive">Received</span>
                                                @break
                                            @case('cancelled')
                                                <span class="status-pill negative">Cancelled</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.purchase-orders.show', $order) }}" class="text-ink hover:text-gold">View</a>
                                        @if($order->status === 'draft')
                                            <a href="{{ route('accounting.purchase-orders.edit', $order) }}" class="text-ink hover:text-gold">Edit</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No purchase orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($orders->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
