<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Invoice') }} #{{ $invoice->invoice_number }}
            </h2>
            <div class="flex items-center space-x-3">
                @if($invoice->status === 'draft')
                    <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Post') }}
                        </button>
                    </form>
                    <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Edit') }}
                    </a>
                @endif
                @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                    <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Are you sure you want to void this invoice?')">
                            {{ __('Void') }}
                        </button>
                    </form>
                @endif
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Print') }}
                </button>
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
                        <dt class="text-sm font-medium text-gray-500">{{ __('Invoice Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @switch($invoice->status)
                                @case('draft')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>
                                    @break
                                @case('sent')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Sent</span>
                                    @break
                                @case('paid')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                                    @break
                                @case('overdue')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Overdue</span>
                                    @break
                                @case('void')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">Void</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Customer') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->customer->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Invoice Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Due Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Reference') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->reference ?? '—' }}</dd>
                    </div>
                    @if($invoice->memo)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->memo }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Line Items') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoice->lines as $line)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $line->product->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $line->description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $line->quantity }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($line->unit_price, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $line->discount }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $line->tax_rate }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">{{ number_format($line->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal:</span>
                            <span class="text-gray-900">{{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tax:</span>
                            <span class="text-gray-900">{{ number_format($invoice->tax_total, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold border-t pt-2">
                            <span class="text-gray-800">Total:</span>
                            <span class="text-gray-900">{{ number_format($invoice->total, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Paid:</span>
                            <span class="text-gray-900">{{ number_format($invoice->amount_paid, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold border-t pt-2">
                            <span class="text-gray-800">Balance Due:</span>
                            <span class="text-gray-900">{{ number_format($invoice->balance_due, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($invoice->journalEntry)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Journal Entry') }}</h3>
                    <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $invoice->journalEntry->reference }} — View Journal Entry
                    </a>
                </div>
            @endif

            @if($invoice->payments->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payment History') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoice->payments as $payment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->reference ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($payment->pivot->amount, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
