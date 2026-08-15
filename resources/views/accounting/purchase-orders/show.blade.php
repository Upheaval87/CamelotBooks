<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $statusMap = [
            'draft' => ['draft', __('Draft')],
            'sent' => ['teal', __('Sent')],
            'partially_received' => ['mint', __('Partially Received')],
            'fully_received' => ['post', __('Fully Received')],
            'closed' => ['gray', __('Closed')],
            'cancelled' => ['red', __('Cancelled')],
        ];
        [$statusCls, $statusLabel] = $statusMap[$order->status] ?? ['gray', ucfirst(str_replace('_', ' ', $order->status))];
        $qtyOrdered = $order->lines->sum('quantity');
        $qtyReceived = $order->lines->sum('quantity_received');
        $qtyBilled = $order->lines->sum('quantity_billed');
        $billableQty = $order->lines->sum(fn($l) => max(0, round((float) $l->quantity_received - (float) $l->quantity_billed, 2)));
        $totalAmount = (float) $order->lines->sum('amount');
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sticky-head">
                <div>
                    <h1>{{ __('Purchase Order') }} <span class="mono-chip">{{ $order->po_number }}</span></h1>
                    <div class="sub">{{ $order->vendor->name ?? '' }}
                        @if($order->date) · {{ $order->date->format('M d, Y') }} @endif
                        @if($order->expected_delivery_date) · {{ __('expected') }} {{ $order->expected_delivery_date->format('M d, Y') }} @endif
                    </div>
                </div>
                <div class="tbtns">
                    @if($order->status === 'draft')
                        @can('purchase-orders.confirm')
                            <form method="POST" action="{{ route('accounting.purchase-orders.confirm', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Confirm and send this order to the vendor?', { type: 'action' })">
                                @csrf
                                <button type="submit" class="btn btn-cta">{{ __('Confirm & Send') }}</button>
                            </form>
                        @endcan
                        <a href="{{ route('accounting.purchase-orders.edit', $order) }}" class="btn btn-sec">{{ __('Edit') }}</a>
                    @endif
                    @if(in_array($order->status, ['sent', 'partially_received']))
                        <a href="{{ route('accounting.goods-received-notes.create', ['purchase_order_id' => $order->id]) }}" class="btn btn-sec">{{ __('Create GRN') }}</a>
                    @endif
                    @if(in_array($order->status, ['sent', 'partially_received', 'fully_received']) && $billableQty > 0)
                        @can('bills.create')
                            <form method="POST" action="{{ route('accounting.purchase-orders.convert', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Create a draft bill for the received quantities on this order?', { type: 'action' })">
                                @csrf
                                <button type="submit" class="btn btn-cta">{{ __('Convert to Bill') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if(in_array($order->status, ['draft', 'sent']))
                        @can('purchase-orders.cancel')
                            <form method="POST" action="{{ route('accounting.purchase-orders.cancel', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Cancel this purchase order?', { type: 'danger' })">
                                @csrf
                                <button type="submit" class="btn btn-danger-o">{{ __('Cancel') }}</button>
                            </form>
                        @endcan
                    @endif
                    <button type="button" onclick="window.print()" class="btn btn-ghost">{{ __('Print') }}</button>
                    <a href="{{ route('accounting.purchase-orders.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <x-input-error :messages="$errors->get('error')" class="mb-4" />

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    {{-- profile --}}
                    <section class="card">
                        <div class="prof">
                            <span class="ava-xl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4M3 7l9 4 9-4M5 10v10m4-7h6m-6 4h6m4-7v10"/></svg></span>
                            <div>
                                <div class="n">{{ __('Purchase Order') }} {{ $order->po_number }} <span class="badge b-{{ $statusCls }}"><span class="bdot"></span>{{ $statusLabel }}</span></div>
                                <div class="c">
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>{{ $order->date?->format('M d, Y') }}</span>
                                    @if($order->expected_delivery_date)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>{{ __('Expected') }} {{ $order->expected_delivery_date->format('M d, Y') }}</span>
                                    @endif
                                    @if($order->branch)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg>{{ $order->branch->name }}</span>
                                    @endif
                                    @if($order->requisition)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 12h6m-6 4h6M9 8h6M7 4h10a2 2 0 0 1 2 2v16H5V6a2 2 0 0 1 2-2z"/></svg>{{ $order->requisition->requisition_number }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- stats --}}
                    <div class="statgrid statgrid--4">
                        <div class="sbox ic"><span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                            <div><div class="l">{{ __('Total') }} ({{ $cs }})</div><div class="v">{{ format_number($totalAmount) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg></span>
                            <div><div class="l">{{ __('Lines') }}</div><div class="v">{{ $order->lines->count() }}</div></div></div>
                        <div class="sbox ic"><span class="t" style="background:rgba(18,143,142,.10);color:var(--sec,#128F8E)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                            <div><div class="l">{{ __('Qty Received') }}</div><div class="v">{{ $qtyReceived }}</div></div></div>
                        <div class="sbox ic"><span class="t" style="background:rgba(70,112,140,.10);color:#46708C"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v3m0 12v3M3 12h3m12 0h3"/></svg></span>
                            <div><div class="l">{{ __('Qty Billed') }}</div><div class="v">{{ $qtyBilled }}</div></div></div>
                    </div>

                    {{-- details --}}
                    <section class="card card-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                            <h2>{{ __('Order Details') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="g4">
                            <div class="field"><label>{{ __('PO Number') }}</label><span class="val mono">{{ $order->po_number }}</span></div>
                            <div class="field"><label>{{ __('Vendor') }}</label><span class="val">{{ $order->vendor->name ?? '—' }}</span></div>
                            <div class="field"><label>{{ __('Date') }}</label><span class="val">{{ $order->date?->format('M d, Y') ?? '—' }}</span></div>
                            <div class="field"><label>{{ __('Expected Delivery') }}</label><span class="val">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</span></div>
                            <div class="field"><label>{{ __('Branch') }}</label><span class="val">{{ $order->branch->name ?? '—' }}</span></div>
                            <div class="field"><label>{{ __('Cost Center') }}</label><span class="val">{{ $order->costCenter->name ?? '—' }}</span></div>
                            <div class="field"><label>{{ __('Requisition') }}</label><span class="val mono">{{ $order->requisition->requisition_number ?? '—' }}</span></div>
                            @if($order->memo)
                                <div class="field sp2"><label>{{ __('Description') }}</label><span class="em" style="font-size:.8125rem">{{ $order->memo }}</span></div>
                            @endif
                        </div>
                    </section>

                    {{-- line items --}}
                    <section class="card card-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                            <h2>{{ __('Line Items') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="li-wrap">
                            <table>
                                <thead><tr>
                                    <th style="width:18%">{{ __('Product') }}</th>
                                    <th style="width:24%">{{ __('Description') }}</th>
                                    <th class="num" style="width:8%">{{ __('Qty') }}</th>
                                    <th class="num" style="width:12%">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th class="num" style="width:14%">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th class="num" style="width:10%">{{ __('Received') }}</th>
                                    <th class="num" style="width:14%">{{ __('Billed') }}</th>
                                </tr></thead>
                                <tbody>
                                    @foreach($order->lines as $line)
                                        <tr>
                                            <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $line->product->name ?? '—' }}</td>
                                            <td class="em">{{ $line->description }}</td>
                                            <td class="numr">{{ $line->quantity }}</td>
                                            <td class="numr">{{ format_number($line->unit_price) }}</td>
                                            <td class="numr" style="font-weight:700">{{ format_number($line->amount) }}</td>
                                            <td class="numr">{{ $line->quantity_received }}</td>
                                            <td class="numr">{{ $line->quantity_billed }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="li-totals">
                            <div class="box">
                                <div class="trow total"><span>{{ __('Total') }}:</span><span class="v">{{ format_number($totalAmount) }}</span></div>
                            </div>
                        </div>
                    </section>

                    {{-- GRNs --}}
                    @if($order->grns->count() > 0)
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg></span>
                                <h2>{{ __('Goods Received Notes') }}</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="li-wrap">
                                <table>
                                    <thead><tr>
                                        <th style="width:24%">{{ __('GRN #') }}</th>
                                        <th style="width:24%">{{ __('Date') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach($order->grns as $grn)
                                            <tr>
                                                <td><a href="{{ route('accounting.goods-received-notes.show', $grn) }}" class="link">{{ $grn->grn_number }}</a></td>
                                                <td class="em">{{ $grn->date?->format('M d, Y') ?? '—' }}</td>
                                                <td>
                                                    @if($grn->status === 'posted')
                                                        <span class="badge b-post"><span class="bdot"></span>{{ __('Posted') }}</span>
                                                    @else
                                                        <span class="badge b-gray"><span class="bdot"></span>{{ ucfirst($grn->status) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endif

                    {{-- journal entry --}}
                    @if($order->journalEntry)
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                <h2>{{ __('Journal Entry') }}</h2>
                                <span class="rule"></span>
                                <a href="{{ route('accounting.journal-entries.show', $order->journalEntry) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a>
                            </div>
                            <p class="sub" style="margin-top:8px">{{ $order->journalEntry->reference ?? $order->journalEntry->journal_number }} — {{ $order->journalEntry->memo ?? '' }}</p>
                        </section>
                    @endif
                </div>

                {{-- rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span><h2>{{ __('Summary') }}</h2></div>
                            <div style="margin-top:8px">
                                <div class="srow"><span class="l">{{ __('Qty Ordered') }}</span><span class="v">{{ $qtyOrdered }}</span></div>
                                <div class="srow"><span class="l">{{ __('Qty Received') }}</span><span class="v">{{ $qtyReceived }}</span></div>
                                <div class="srow"><span class="l">{{ __('Qty Billed') }}</span><span class="v">{{ $qtyBilled }}</span></div>
                                <div class="srow strong"><span class="l">{{ __('Total') }}</span><span class="v">{{ format_number($totalAmount) }}</span></div>
                            </div>
                            <div class="gt"><span class="l">{{ __('Status') }}</span><span class="v">{{ strtoupper(str_replace('_', ' ', $order->status)) }}</span></div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                <button type="button" onclick="window.print()" class="vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z"/></svg></span>{{ __('Print') }}</button>
                                <a href="{{ route('accounting.purchase-orders.create') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v16m8-8H4"/></svg></span>{{ __('New Purchase Order') }}</a>
                                <a href="{{ route('accounting.goods-received-notes.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg></span>{{ __('Goods Received Notes') }}</a>
                                <a href="{{ route('accounting.purchase-orders.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7 7-7M3 12h18"/></svg></span>{{ __('All Purchase Orders') }}</a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
