<x-app-layout>
    <x-slot name="header">{{ __('Payment Settlements') }}</x-slot>

    <div class="flex justify-end mb-4 px-4 sm:px-0">
        <x-button variant="primary" href="{{ route('pos.settlements.create') }}">{{ __('Record Settlement') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <table class="datasheet">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Payment Method</th>
                            <th>Bank Account</th>
                            <th>Period</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Fee</th>
                            <th class="text-right">Net</th>
                            <th class="text-center">Status</th>
                            <th>Settled By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('pos.settlements.show', $settlement->id) }}'">
                                <td>{{ $settlement->settlement_number }}</td>
                                <td class="text-ink-soft">{{ $settlement->paymentMethod->name ?? '—' }}</td>
                                <td class="text-ink-soft">{{ $settlement->bankAccount->name ?? '—' }}</td>
                                <td class="text-ink-soft">{{ $settlement->period_start }} – {{ $settlement->period_end }}</td>
                                <td class="numeric">@money($settlement->total_amount)</td>
                                <td class="numeric text-red-600">@money($settlement->fee_amount)</td>
                                <td class="numeric font-semibold">@money($settlement->net_amount)</td>
                                <td class="text-center">
                                    @if($settlement->status === 'posted')
                                        <span class="status-pill positive">Posted</span>
                                    @else
                                        <span class="status-pill negative">Draft</span>
                                    @endif
                                </td>
                                <td class="text-ink-soft">{{ $settlement->settledBy->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-ink-soft text-center">No settlements recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $settlements->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
