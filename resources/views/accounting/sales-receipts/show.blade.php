<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sales Receipt {{ $salesReceipt->receipt_number }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Print') }}
                </a>
                <a href="{{ route('accounting.sales-receipts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Sales Receipts') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <span class="text-sm text-gray-500">Status:</span>
                        <span class="ml-2">
                            @switch($salesReceipt->status)
                                @case('draft')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>
                                    @break
                                @case('posted')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Posted</span>
                                    @break
                                @case('voided')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Voided</span>
                                    @break
                            @endswitch
                        </span>
                    </div>
                    <div><span class="text-sm text-gray-500">Customer:</span> <span class="ml-2 text-gray-900">{{ $salesReceipt->customer->name ?? 'Walk-in' }}</span></div>
                    <div><span class="text-sm text-gray-500">Date:</span> <span class="ml-2 text-gray-900">{{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Reference:</span> <span class="ml-2 text-gray-900">{{ $salesReceipt->reference ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Memo:</span> <span class="ml-2 text-gray-900">{{ $salesReceipt->memo ?? '—' }}</span></div>
                    <div><span class="text-sm text-gray-500">Branch:</span> <span class="ml-2 text-gray-900">{{ $salesReceipt->branch->name ?? '—' }}</span></div>
                    @if($salesReceipt->journal_entry_id)
                        <div><span class="text-sm text-gray-500">Journal Entry:</span> <a href="{{ route('accounting.journal-entries.show', $salesReceipt->journal_entry_id) }}" class="ml-2 text-indigo-600 hover:text-indigo-900">JE-{{ str_pad($salesReceipt->journal_entry_id, 4, '0', STR_PAD_LEFT) }}</a></div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Line Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($salesReceipt->lines as $line)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $line->product->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $line->description }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($line->quantity, 2) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ format_money($line->unit_price) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($line->discount, 2) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ format_money($line->tax_amount) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right font-medium">{{ format_money($line->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal:</span>
                            <span class="text-gray-900">{{ format_money($salesReceipt->subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tax:</span>
                            <span class="text-gray-900">{{ format_money($salesReceipt->tax_total) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold border-t pt-2">
                            <span class="text-gray-800">Total:</span>
                            <span class="text-gray-900">{{ format_money($salesReceipt->total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Payments</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cash Tendered</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($salesReceipt->payments as $payment)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $payment->paymentMethod->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ format_money($payment->amount) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ $payment->cash_tendered ? format_money($payment->cash_tendered) : '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ $payment->change_given ? format_money($payment->change_given) : '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $payment->reference_number ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
                <div class="flex flex-wrap gap-3">
                    @if($salesReceipt->status === 'draft')
                        <form method="POST" action="{{ route('accounting.sales-receipts.post', $salesReceipt) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Post Receipt') }}
                            </button>
                        </form>
                    @endif
                    @if($salesReceipt->status === 'posted' && $salesReceipt->customer && $salesReceipt->customer->email)
                        <form method="POST" action="{{ route('accounting.sales-receipts.email', $salesReceipt) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 shadow-sm hover:bg-gray-50">Email Receipt</button>
                        </form>
                    @endif
                    @if($salesReceipt->status === 'posted')
                        <form method="POST" action="{{ route('accounting.sales-receipts.void', $salesReceipt) }}" class="inline" onsubmit="return prompt('Enter void reason:')">
                            @csrf
                            <input type="hidden" name="void_reason" value="Voided via UI" />
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Void Receipt') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
