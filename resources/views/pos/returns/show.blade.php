<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('POS Return') }} {{ $return->return_number }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            <div class="card p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-ink-soft">Return #</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $return->return_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-ink-soft">Original Sale</p>
                        <p class="text-lg font-semibold text-gray-900">
                            @if($return->sale)
                                <a href="{{ route('pos.sales.receipt', $return->sale) }}" class="text-indigo-600 hover:text-indigo-900">{{ $return->sale->sale_number }}</a>
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-ink-soft">Date</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $return->date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-ink-soft">Status</p>
                        @if($return->isPosted())
                            <span class="status-pill positive">Posted</span>
                        @elseif($return->isDraft())
                            <span class="status-pill negative">Draft</span>
                        @else
                            <span class="status-pill negative">Voided</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-ink-soft">Reason</p>
                        <p class="text-sm font-medium text-gray-900">{{ $return->reason ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-ink-soft">Created By</p>
                        <p class="text-sm font-medium text-gray-900">{{ $return->creator?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-ink-soft">Posted At</p>
                        <p class="text-sm font-medium text-gray-900">{{ $return->posted_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-ink-soft">Refund Total</p>
                        <p class="text-lg font-semibold text-red-600">-@money($return->total)</p>
                    </div>
                </div>
            </div>

            <div class="card p-6 mb-6">
                <div class="form-section-label">2 · Returned Items</div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Qty Returned</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Tax</th>
                                <th class="text-right">Line Total</th>
                                <th class="text-right">COGS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($return->lines as $line)
                                <tr>
                                    <td>{{ $line->product?->name ?? '—' }}</td>
                                    <td class="numeric">{{ number_format($line->quantity_returned, 4) }}</td>
                                    <td class="numeric">@money($line->unit_price)</td>
                                    <td class="numeric">@money($line->tax_amount)</td>
                                    <td class="numeric text-red-600 font-semibold">-@money($line->line_total)</td>
                                    <td class="numeric">{{ $line->cost_of_goods !== null ? format_money($line->cost_of_goods) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right font-semibold">Subtotal:</td>
                                <td class="numeric">@money($return->subtotal)</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right font-semibold">Tax:</td>
                                <td class="numeric">@money($return->tax_total)</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right font-bold">Total Refund:</td>
                                <td class="numeric text-red-600 font-bold">-@money($return->total)</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($return->journalEntry)
                <div class="card p-6">
                    <div class="form-section-label">3 · Journal Entry
                        <a href="{{ route('accounting.journal-entries.show', $return->journalEntry) }}" class="text-sm text-indigo-600 hover:text-indigo-900 ml-2">
                            #{{ $return->journalEntry->journal_number }}
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th class="text-right">Debit ({{ $cs }})</th>
                                    <th class="text-right">Credit ({{ $cs }})</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($return->journalEntry->lines as $line)
                                    <tr>
                                        <td>
                                            {{ $line->account?->code }} – {{ $line->account?->name }}
                                        </td>
                                        <td class="numeric">
                                            {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                        </td>
                                        <td class="numeric">
                                            {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                        </td>
                                        <td class="text-ink-soft">{{ $line->description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <x-button variant="ghost" href="{{ route('pos.returns.index') }}">&larr; Back to Returns</x-button>
            </div>
        </div>
    </div>
</x-app-layout>
