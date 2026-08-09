<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $received = $salesReceipt->payments->sum('amount');
        $unallocated = max($salesReceipt->total - $received, 0);
        $methodLine = $salesReceipt->payments->first()?->paymentMethod?->name ?? '—';
    @endphp

    <div class="sr-suite py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §4 sticky page head (toolbar) --}}
            <div class="sticky-head">
                <div class="toolbar" style="width:100%">
                    <div>
                        <div class="glabel">{{ __('Create') }}</div>
                        <div class="tbtns">
                            <a href="{{ route('accounting.sales-receipts.create', $salesReceipt->customer_id ? ['customer_id' => $salesReceipt->customer_id] : []) }}" class="btn btn-ghost btn-sm">＋ {{ __('New') }}</a>
                            @if($salesReceipt->status === 'draft')
                                <a href="{{ route('accounting.sales-receipts.edit', $salesReceipt) }}" class="btn btn-sec btn-sm">✎ {{ __('Edit') }}</a>
                            @endif
                        </div>
                    </div>
                    <span class="tdiv"></span>
                    <div>
                        <div class="glabel">{{ __('Document') }}</div>
                        <div class="tbtns">
                            <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">🖨 {{ __('Print / PDF') }}</a>
                            @if($salesReceipt->status === 'posted' && $salesReceipt->customer && $salesReceipt->customer->email)
                                <form method="POST" action="{{ route('accounting.sales-receipts.email', $salesReceipt) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">✉ {{ __('Email Receipt') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    {{-- §4 profile header --}}
                    <section class="card">
                        <div class="prof">
                            <span class="ava-xl"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div>
                                <div class="n">{{ __('Receipt') }} {{ $salesReceipt->receipt_number }}
                                    @switch($salesReceipt->status)
                                        @case('draft') <span class="badge b-draft"><span class="bdot"></span>{{ __('Draft') }}</span> @break
                                        @case('posted') <span class="badge b-post"><span class="bdot"></span>{{ __('Posted') }}</span> @break
                                        @case('voided') <span class="badge b-void"><span class="bdot"></span>{{ __('Voided') }}</span> @break
                                    @endswitch
                                </div>
                                <div class="c">
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $salesReceipt->customer->name ?? __('Walk-in Customer') }}</span>
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</span>
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $methodLine }}</span>
                                    @if($salesReceipt->branch)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 9l9-5.5L21 9M5 9.5V19M9.5 9.5V19M14.5 9.5V19M19 9.5V19M3 19.5h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $salesReceipt->branch->name }}</span>
                                    @endif
                                    @if($salesReceipt->reference)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12h6M8 6h8M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>{{ __('Ref') }} {{ $salesReceipt->reference }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="acts">
                                @if($salesReceipt->status === 'draft')
                                    <a href="{{ route('accounting.sales-receipts.edit', $salesReceipt) }}" class="btn btn-sec btn-sm">✎ {{ __('Edit') }}</a>
                                    @can('sales-receipts.post')
                                        <a href="{{ route('accounting.sales-receipts.post-page', $salesReceipt) }}" class="btn btn-cta btn-sm">{{ __('Post Receipt') }}</a>
                                    @endcan
                                @endif
                                @if($salesReceipt->status === 'posted')
                                    @can('sales-receipts.void')
                                        <form method="POST" action="{{ route('accounting.sales-receipts.void', $salesReceipt) }}" class="inline" onsubmit="return fbPromptForm(event, '{{ __('Enter void reason') }}:')">
                                            @csrf<input type="hidden" name="void_reason" value="" />
                                            <button type="submit" class="btn btn-danger-o btn-sm">⊘ {{ __('Void') }}</button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </section>

                    {{-- §4 stat grid --}}
                    <div class="sgrid">
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v10M15 9.5c-.6-1-1.7-1.5-3-1.5-1.8 0-3 .9-3 2.2 0 2.8 6 1.6 6 4.3 0 1.3-1.2 2.2-3 2.2-1.3 0-2.4-.5-3-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Total') }} ({{ $cs }})</div><div class="v">{{ format_number($salesReceipt->total) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div><div class="l">{{ __('Received') }} ({{ $cs }})</div><div class="v mint">{{ format_number($received) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Unallocated') }} ({{ $cs }})</div><div class="v">{{ format_number($unallocated) }}</div></div></div>
                    </div>

                    {{-- §4 contents + tabs --}}
                    <section class="card">
                        <div class="card-sec" style="padding-bottom:8px">
                            <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Contents') }}</h2><span class="rule"></span></div>
                            <div class="tabs" role="tablist">
                                <button type="button" class="tab on" data-target="tab-payments" role="tab">{{ __('Payments') }}</button>
                                <button type="button" class="tab" data-target="tab-lines" role="tab">{{ __('Line Items') }}</button>
                                <button type="button" class="tab" data-target="tab-activity" role="tab">{{ __('Activity') }}</button>
                            </div>

                            <div class="tpanels">
                                {{-- payments tab --}}
                                <section id="tab-payments" class="tpanel">
                                    <div class="li-wrap">
                                        <table>
                                            <thead><tr>
                                                <th style="width:24%">{{ __('Method') }}</th>
                                                <th style="width:40%">{{ __('Reference') }}</th>
                                                <th class="num" style="width:18%">{{ __('Amount') }} ({{ $cs }})</th>
                                                <th class="num" style="width:18%">{{ __('Allocated') }} ({{ $cs }})</th>
                                            </tr></thead>
                                            <tbody>
                                                @foreach($salesReceipt->payments as $payment)
                                                    <tr>
                                                        <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $payment->paymentMethod->name ?? '—' }}</td>
                                                        <td class="em">{{ $payment->reference_number ?? '—' }}</td>
                                                        <td class="numr">{{ format_number($payment->amount) }}</td>
                                                        <td class="numr">{{ format_number($payment->amount) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div style="display:flex;justify-content:flex-end;margin-top:1rem">
                                        <div class="li-totals" style="width:16rem;margin:0"><div class="box">
                                            <div class="trow"><span>{{ __('Received') }}</span><span class="v">{{ format_number($received) }}</span></div>
                                            <div class="trow"><span>{{ __('Unallocated') }}</span><span class="v">{{ format_number($unallocated) }}</span></div>
                                            <div class="trow total"><span>{{ __('Total') }}</span><span class="v">{{ format_number($salesReceipt->total) }}</span></div>
                                        </div></div>
                                    </div>
                                </section>

                                {{-- line items tab --}}
                                <section id="tab-lines" class="tpanel" style="display:none">
                                    <div class="li-wrap">
                                        <table>
                                            <thead><tr>
                                                <th style="width:20%">{{ __('Product') }}</th>
                                                <th style="width:26%">{{ __('Description') }}</th>
                                                <th class="num" style="width:9%">{{ __('Qty') }}</th>
                                                <th class="num" style="width:13%">{{ __('Price') }} ({{ $cs }})</th>
                                                <th class="num" style="width:12%">{{ __('Discount') }}</th>
                                                <th class="num" style="width:10%">{{ __('Tax') }} ({{ $cs }})</th>
                                                <th class="num" style="width:10%">{{ __('Total') }} ({{ $cs }})</th>
                                            </tr></thead>
                                            <tbody>
                                                @foreach($salesReceipt->lines as $line)
                                                    <tr>
                                                        <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $line->product->name ?? '—' }}</td>
                                                        <td class="em">{{ $line->description }}</td>
                                                        <td class="numr">{{ number_format($line->quantity, 2) }}</td>
                                                        <td class="numr">{{ format_number($line->unit_price) }}</td>
                                                        <td class="numr">{{ number_format($line->discount, 2) }}</td>
                                                        <td class="numr">{{ format_number($line->tax_amount) }}</td>
                                                        <td class="numr" style="font-weight:800;color:var(--ink,#0B2A2D)">{{ format_number($line->line_total) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div style="display:flex;justify-content:flex-end;margin-top:1rem">
                                        <div class="li-totals" style="width:16rem;margin:0"><div class="box">
                                            <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v">{{ format_number($salesReceipt->subtotal) }}</span></div>
                                            <div class="trow"><span>{{ __('Tax') }}</span><span class="v">{{ format_number($salesReceipt->tax_total) }}</span></div>
                                            <div class="trow total"><span>{{ __('Total') }}</span><span class="v">{{ format_number($salesReceipt->total) }}</span></div>
                                        </div></div>
                                    </div>
                                </section>

                                {{-- activity tab --}}
                                <section id="tab-activity" class="tpanel" style="display:none">
                                    <div class="g4">
                                        <div class="field"><span class="label">{{ __('Receipt Number') }}</span><span class="val mono">{{ $salesReceipt->receipt_number }}</span></div>
                                        <div class="field"><span class="label">{{ __('Customer') }}</span><span class="val">{{ $salesReceipt->customer->name ?? __('Walk-in') }}</span></div>
                                        <div class="field"><span class="label">{{ __('Date') }}</span><span class="val">{{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</span></div>
                                        <div class="field"><span class="label">{{ __('Reference') }}</span><span class="val mono">{{ $salesReceipt->reference ?? '—' }}</span></div>
                                        <div class="field"><span class="label">{{ __('Branch') }}</span><span class="val">{{ $salesReceipt->branch->name ?? '—' }}</span></div>
                                        <div class="field"><span class="label">{{ __('Cost Center') }}</span><span class="val">{{ $salesReceipt->costCenter->name ?? '—' }}</span></div>
                                        <div class="field"><span class="label">{{ __('Created By') }}</span><span class="val">{{ $salesReceipt->createdByUser->name ?? '—' }}</span></div>
                                        <div class="field"><span class="label">{{ __('Posted By') }}</span><span class="val">{{ $salesReceipt->postedByUser->name ?? '—' }}</span></div>
                                        <div class="field"><span class="label">{{ __('Posted At') }}</span><span class="val">{{ $salesReceipt->posted_at?->format('M d, Y H:i') ?? '—' }}</span></div>
                                        @if($salesReceipt->journal_entry_id)
                                            <div class="field"><span class="label">{{ __('Journal Entry') }}</span><a href="{{ route('accounting.journal-entries.show', $salesReceipt->journal_entry_id) }}" class="val mono link">{{ __('JE-') }}{{ str_pad($salesReceipt->journal_entry_id, 4, '0', STR_PAD_LEFT) }}</a></div>
                                        @endif
                                        @if($salesReceipt->memo)
                                            <div class="field" style="grid-column: span 2"><span class="label">{{ __('Description') }}</span><span class="val">{{ $salesReceipt->memo }}</span></div>
                                        @endif
                                    </div>
                                    @if($salesReceipt->status === 'voided')
                                        <div class="note-info" style="margin-top:1rem">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <span>{{ __('Voided') }}: {{ $salesReceipt->void_reason ?? __('No reason provided') }} @if($salesReceipt->voidedByUser) · {{ $salesReceipt->voidedByUser->name }} @endif @if($salesReceipt->voided_at) · {{ $salesReceipt->voided_at->format('M d, Y H:i') }} @endif</span>
                                        </div>
                                    @endif
                                </section>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- §4 rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7.5h8M8.5 12h.01M12 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Summary') }}</h2></div>
                            <div style="margin-top:8px">
                                <div class="srow"><span class="l">{{ __('Subtotal') }}</span><span class="v">{{ format_number($salesReceipt->subtotal) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Tax') }}</span><span class="v">{{ format_number($salesReceipt->tax_total) }}</span></div>
                                <div class="srow strong"><span class="l">{{ __('Total') }}</span><span class="v">{{ format_number($salesReceipt->total) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Received') }}</span><span class="v">{{ format_number($received) }}</span></div>
                            </div>
                            <div class="gt"><span class="l">{{ __('Total Received') }}</span><span class="v">{{ $cs }}{{ format_number($received) }}</span></div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                @if($salesReceipt->customer_id)
                                    <a href="{{ route('accounting.invoices.create', ['customer_id' => $salesReceipt->customer_id]) }}" class="vitem">
                                        <span class="ic">🗎</span>{{ __('New Invoice') }}
                                    </a>
                                @endif
                                @if($salesReceipt->status === 'draft')
                                    @can('sales-receipts.post')
                                        <a href="{{ route('accounting.sales-receipts.post-page', $salesReceipt) }}" class="vitem">
                                            <span class="ic">📤</span>{{ __('Post Receipt') }}
                                        </a>
                                    @endcan
                                @endif
                                <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" rel="noopener" class="vitem">
                                    <span class="ic">🖨</span>{{ __('Print / PDF') }}
                                </a>
                                <a href="{{ route('accounting.sales-receipts.index') }}" class="vitem">
                                    <span class="ic">←</span>{{ __('All Receipts') }}
                                </a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.sr-suite .tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.sr-suite .tab').forEach(t => t.classList.remove('on'));
                tab.classList.add('on');
                document.querySelectorAll('.sr-suite .tpanel').forEach(p => {
                    p.style.display = (p.id === tab.dataset.target) ? '' : 'none';
                });
            });
        });
    </script>
</x-app-layout>
