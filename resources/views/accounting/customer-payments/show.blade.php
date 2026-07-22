<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Customer Payment') }}
            </h2>
            <div class="flex items-center space-x-3">
                @if($payment->status !== 'void')
                    <form method="POST" action="{{ route('accounting.customer-payments.void', $payment) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Are you sure you want to void this payment?')">
                            {{ __('Void') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('accounting.invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Invoices') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                        <dt class="text-sm font-medium text-gray-500">{{ __('Customer') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $payment->customer->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($payment->status === 'void')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">Void</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Payment Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Amount') }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($payment->amount, 2) }}</dd>
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
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Invoice Allocations') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Allocated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($payment->invoices as $invoice)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="{{ route('accounting.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($invoice->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                        {{ number_format($invoice->pivot->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
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
