<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('POS Return') }} {{ $return->return_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Return #</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $return->return_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Original Sale</p>
                        <p class="text-lg font-semibold text-gray-900">
                            @if($return->sale)
                                <a href="{{ route('pos.sales.receipt', $return->sale) }}" class="text-indigo-600 hover:text-indigo-900">{{ $return->sale->sale_number }}</a>
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $return->date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        @if($return->isPosted())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Posted</span>
                        @elseif($return->isDraft())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Voided</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Reason</p>
                        <p class="text-sm font-medium text-gray-900">{{ $return->reason ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Created By</p>
                        <p class="text-sm font-medium text-gray-900">{{ $return->creator?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Posted At</p>
                        <p class="text-sm font-medium text-gray-900">{{ $return->posted_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Refund Total</p>
                        <p class="text-lg font-semibold text-red-600">-@money($return->total)</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Returned Items') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty Returned</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tax</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Line Total</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">COGS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($return->lines as $line)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $line->product?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900">{{ number_format($line->quantity_returned, 4) }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900">@money($line->unit_price)</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900">@money($line->tax_amount)</td>
                                    <td class="px-4 py-2 text-sm text-right text-red-600 font-semibold">-@money($line->line_total)</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900">{{ $line->cost_of_goods !== null ? format_money($line->cost_of_goods) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-sm font-semibold text-gray-900 text-right">Subtotal:</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-900">@money($return->subtotal)</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-sm font-semibold text-gray-900 text-right">Tax:</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-900">@money($return->tax_total)</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-sm font-bold text-gray-900 text-right">Total Refund:</td>
                                <td class="px-4 py-2 text-sm font-bold text-red-600 text-right">-@money($return->total)</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($return->journalEntry)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        {{ __('Journal Entry') }}
                        <a href="{{ route('accounting.journal-entries.show', $return->journalEntry) }}" class="text-sm text-indigo-600 hover:text-indigo-900 ml-2">
                            #{{ $return->journalEntry->journal_number }}
                        </a>
                    </h3>
                    <div class="overflow-x-auto">
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
                                @foreach($return->journalEntry->lines as $line)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ $line->account?->code }} – {{ $line->account?->name }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-gray-900">
                                            {{ $line->debit > 0 ? format_money($line->debit) : '' }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-gray-900">
                                            {{ $line->credit > 0 ? format_money($line->credit) : '' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $line->description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('pos.returns.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Returns</a>
            </div>
        </div>
    </div>
</x-app-layout>
