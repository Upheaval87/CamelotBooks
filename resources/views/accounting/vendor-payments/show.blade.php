<x-app-layout>
    <x-slot name="header">{{ __('Vendor Payment') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        @if($payment->status !== 'void')
            <form method="POST" action="{{ route('accounting.vendor-payments.void', $payment) }}" class="inline">
                @csrf
                @method('PATCH')
                <x-button variant="ghost" type="submit" onclick="return confirm('Are you sure you want to void this payment?')">{{ __('Void') }}</x-button>
            </form>
        @endif
        <x-button variant="ghost" href="{{ route('accounting.bills.index') }}">{{ __('Back to Bills') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Vendor') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $payment->vendor->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($payment->status === 'void')
                                <span class="status-pill neutral">Void</span>
                            @else
                                <span class="status-pill positive">Completed</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Payment Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Amount') }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ format_money($payment->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Payment Method') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Bank Account') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $payment->bankAccount->name ?? '—' }}</dd>
                    </div>
                    @if($payment->reference)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Reference') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->reference }}</dd>
                        </div>
                    @endif
                    @if($payment->memo)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->memo }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Bill Allocations') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Bill #</th>
                                <th>Date</th>
                                <th class="text-right">Bill Total</th>
                                <th class="text-right">Amount Allocated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payment->bills as $bill)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.bills.show', $bill) }}" class="text-ink hover:text-gold">
                                            {{ $bill->bill_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $bill->bill_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($bill->total) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($bill->pivot->amount) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">
                                        No allocations found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
