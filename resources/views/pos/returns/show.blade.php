<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>POS Return {{ $return->return_number }}</h1>
                <div class="pos-sub">
                    @if($return->isPosted())
                        <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Posted</span>
                    @elseif($return->isDraft())
                        <span class="pos-badge pos-badge-pend"><span class="pos-bdot"></span>Draft</span>
                    @else
                        <span class="pos-badge pos-badge-rev"><span class="pos-bdot"></span>Voided</span>
                    @endif
                </div>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.returns.index') }}" class="pos-btn pos-btn-ghost">Back to Returns</a>
            </div>
        </div>

        <div class="pos-shell">
            <div>
                {{-- Summary --}}
                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">Return Summary</span>
                    </div>
                    <div class="pos-pad">
                        <div class="pos-g4">
                            <div class="pos-f">
                                <label>Return #</label>
                                <div class="pos-mono pos-bold">{{ $return->return_number }}</div>
                            </div>
                            <div class="pos-f">
                                <label>Original Sale</label>
                                <div class="pos-bold">
                                    @if($return->sale)
                                        <a href="{{ route('pos.sales.receipt', $return->sale) }}" style="color:var(--pos-sec);font-weight:700;text-decoration:none">{{ $return->sale->sale_number }}</a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="pos-f">
                                <label>Date</label>
                                <div class="pos-bold">{{ $return->date?->format('d M Y') ?? '—' }}</div>
                            </div>
                            <div class="pos-f">
                                <label>Refund Total</label>
                                <div class="num pos-bold" style="color:var(--pos-red)">-{{ format_money($return->total) }}</div>
                            </div>
                        </div>
                        <div class="pos-g3" style="margin-top:12px">
                            <div class="pos-f">
                                <label>Reason</label>
                                <div>{{ $return->reason ?? '—' }}</div>
                            </div>
                            <div class="pos-f">
                                <label>Created By</label>
                                <div>{{ $return->creator?->name ?? '—' }}</div>
                            </div>
                            <div class="pos-f">
                                <label>Posted At</label>
                                <div>{{ $return->posted_at?->format('d M Y H:i') ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Returned Items --}}
                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">Returned Items</span>
                    </div>
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="num">Qty Returned</th>
                                    <th class="num">Unit Price</th>
                                    <th class="num">Tax</th>
                                    <th class="num">Line Total</th>
                                    <th class="num">COGS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($return->lines as $line)
                                    <tr>
                                        <td class="pos-bold">{{ $line->product?->name ?? '—' }}</td>
                                        <td class="num">{{ number_format($line->quantity_returned, 4) }}</td>
                                        <td class="num">{{ format_money($line->unit_price) }}</td>
                                        <td class="num">{{ format_money($line->tax_amount) }}</td>
                                        <td class="num pos-bold" style="color:var(--pos-red)">-{{ format_money($line->line_total) }}</td>
                                        <td class="num">{{ $line->cost_of_goods !== null ? format_money($line->cost_of_goods) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="num">Subtotal:</td>
                                    <td class="num pos-bold">{{ format_money($return->subtotal) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="num">Tax:</td>
                                    <td class="num pos-bold">{{ format_money($return->tax_total) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="num">Total Refund:</td>
                                    <td class="num pos-bold" style="color:var(--pos-red)">-{{ format_money($return->total) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Journal Entry --}}
                @if($return->journalEntry)
                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">Journal Entry
                                <a href="{{ route('accounting.journal-entries.show', $return->journalEntry) }}" style="color:var(--pos-sec);font-weight:700;text-decoration:none;margin-left:8px">
                                    #{{ $return->journalEntry->journal_number }}
                                </a>
                            </span>
                        </div>
                        <div class="pos-li-wrap">
                            <table class="pos-tbl">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th class="num">Debit ({{ $cs }})</th>
                                        <th class="num">Credit ({{ $cs }})</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($return->journalEntry->lines as $line)
                                        <tr>
                                            <td class="pos-mono">{{ $line->account?->code }} – {{ $line->account?->name }}</td>
                                            <td class="num">{{ $line->debit > 0 ? format_number($line->debit) : '' }}</td>
                                            <td class="num">{{ $line->credit > 0 ? format_number($line->credit) : '' }}</td>
                                            <td class="pos-em">{{ $line->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.returns.index') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                        All Returns
                    </a>
                    <a href="{{ route('pos.returns.create') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        New Return
                    </a>
                    @if($return->sale)
                        <a href="{{ route('pos.sales.receipt', $return->sale) }}" class="pos-rail-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            Original Sale
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
