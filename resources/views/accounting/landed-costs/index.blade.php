<x-app-layout>
    <x-slot name="header">{{ __('Create Landed Cost') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.landed-costs.create') }}">
                    {{ __('Create Landed Cost') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Voucher #</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Method</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vouchers as $voucher)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.landed-costs.show', $voucher) }}" class="text-ink hover:text-gold">{{ $voucher->voucher_number }}</a>
                                    </td>
                                    <td class="text-ink-soft">{{ $voucher->date->format('M d, Y') }}</td>
                                    <td class="text-ink-soft">{{ $voucher->vendor->name ?? 'N/A' }}</td>
                                    <td class="text-ink-soft">{{ str_replace('_', ' ', ucfirst($voucher->allocation_method)) }}</td>
                                    <td class="numeric">{{ format_money($voucher->total_amount) }}</td>
                                    <td class="text-center">
                                        @if($voucher->status === 'posted')
                                            <span class="status-pill positive">Posted</span>
                                        @elseif($voucher->status === 'draft')
                                            <span class="status-pill neutral">Draft</span>
                                        @else
                                            <span class="status-pill negative">{{ ucfirst($voucher->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('accounting.landed-costs.show', $voucher) }}" class="text-ink hover:text-gold">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-ink-soft">No landed cost vouchers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
