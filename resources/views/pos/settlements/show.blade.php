<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settlement') }} – {{ $settlement->settlement_number }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Settlement Details --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Settlement Details</h3>

                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd>
                                @if($settlement->status === 'posted')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Posted</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Draft</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Payment Method</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $settlement->paymentMethod->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Bank Account</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $settlement->bankAccount->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Period</dt>
                            <dd class="text-sm text-gray-900">{{ $settlement->period_start }} – {{ $settlement->period_end }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Reference</dt>
                            <dd class="text-sm text-gray-900">{{ $settlement->reference ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Settled By</dt>
                            <dd class="text-sm text-gray-900">{{ $settlement->settledBy->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Settled At</dt>
                            <dd class="text-sm text-gray-900">{{ $settlement->settled_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        </div>
                        @if($settlement->notes)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Notes</dt>
                                <dd class="text-sm text-gray-900">{{ $settlement->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Amounts --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Amounts</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Settled</span>
                            <span class="font-medium">${{ number_format($settlement->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Processing Fee</span>
                            <span class="text-red-600">-${{ number_format($settlement->fee_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span>Net Deposit</span>
                            <span>${{ number_format($settlement->net_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Journal Entry --}}
            @if($settlement->journalEntry)
                <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Journal Entry – {{ $settlement->journalEntry->reference }}</h3>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($settlement->journalEntry->lines as $line)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        {{ $line->account->code }} – {{ $line->account->name }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        {{ $line->debit > 0 ? '$' . number_format($line->debit, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        {{ $line->credit > 0 ? '$' . number_format($line->credit, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $line->description }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('pos.settlements.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                    &larr; Back to Settlements
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
