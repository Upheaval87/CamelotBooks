<x-app-layout>
    <x-list-header title="{{ __('New Transfer') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.stock-transfers.create') }}">
                    {{ __('New Transfer') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Transfer #</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.stock-transfers.show', $transfer) }}" class="text-ink hover:text-gold">
                                            {{ $transfer->transfer_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">{{ $transfer->date->format('M d, Y') }}</td>
                                    <td>{{ $transfer->product->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $transfer->fromBranch->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $transfer->toBranch->name ?? '—' }}</td>
                                    <td class="numeric">{{ format_money($transfer->quantity) }}</td>
                                    <td class="text-center">
                                        @if($transfer->status === 'completed')
                                            <span class="status-pill positive">Completed</span>
                                        @else
                                            <span class="status-pill neutral">{{ ucfirst($transfer->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No stock transfers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $transfers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
