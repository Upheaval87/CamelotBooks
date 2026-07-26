<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Payment Settlements') }}</h2>
            <a href="{{ route('pos.settlements.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Record Settlement') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Fee</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Settled By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($settlements as $settlement)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('pos.settlements.show', $settlement->id) }}'">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $settlement->settlement_number }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $settlement->paymentMethod->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $settlement->bankAccount->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $settlement->period_start }} – {{ $settlement->period_end }}</td>
                                <td class="px-6 py-4 text-sm text-right">@money($settlement->total_amount)</td>
                                <td class="px-6 py-4 text-sm text-right text-red-600">@money($settlement->fee_amount)</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold">@money($settlement->net_amount)</td>
                                <td class="px-6 py-4 text-center">
                                    @if($settlement->status === 'posted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Posted</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Draft</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $settlement->settledBy->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">No settlements recorded yet.</td>
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
