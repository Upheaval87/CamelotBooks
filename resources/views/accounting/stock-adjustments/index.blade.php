<x-app-layout>
    <x-list-header title="{{ __('New Adjustment') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.stock-adjustments.create') }}">
                    {{ __('New Adjustment') }}
                </x-button>
            </div>
            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Adj #</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Total Cost</th>
                                <th>Reason</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adjustment)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.stock-adjustments.show', $adjustment) }}" class="text-ink hover:text-gold">
                                            {{ $adjustment->adjustment_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">{{ $adjustment->date->format('M d, Y') }}</td>
                                    <td>{{ $adjustment->product->name ?? '—' }}</td>
                                    <td>
                                        @if($adjustment->type === 'increase')
                                            <span class="status-pill positive">Increase</span>
                                        @else
                                            <span class="status-pill negative">Decrease</span>
                                        @endif
                                    </td>
                                    <td class="numeric">{{ format_money($adjustment->quantity) }}</td>
                                    <td class="numeric">@money($adjustment->total_cost)</td>
                                    <td class="text-ink-soft capitalize">{{ str_replace('_', ' ', $adjustment->reason_code) }}</td>
                                    <td class="text-center">
                                        @if($adjustment->status === 'posted')
                                            <span class="status-pill positive">Posted</span>
                                        @else
                                            <span class="status-pill neutral">{{ ucfirst($adjustment->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-ink-soft">
                                        No stock adjustments found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $adjustments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
