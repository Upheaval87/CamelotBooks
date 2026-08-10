<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $vcSubtotal = $vendorCredit->subtotal;
        $vcTax = $vendorCredit->tax_total;
        $vcTotal = $vendorCredit->total;
        $vcApplied = (float) $vendorCredit->amount_applied;
        $vcAvailable = $vendorCredit->available;
        $statusMap = [
            'draft' => ['draft', __('Draft')],
            'posted' => ['teal', __('Posted')],
            'applied' => ['act', __('Applied')],
            'void' => ['void', __('Void')],
        ];
        [$statusCls, $statusLabel] = $statusMap[$vendorCredit->status] ?? ['gray', ucfirst(str_replace('_', ' ', $vendorCredit->status))];
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Vendor Credit') }} <span class="mono-chip">{{ $vendorCredit->credit_note_number }}</span></h1>
                    <div class="sub">{{ $vendorCredit->vendor->name ?? __('—') }} · {{ __('issued') }} {{ $vendorCredit->credit_note_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="tbtns">
                    @if($vendorCredit->status === 'draft')
                        <form method="POST" action="{{ route('accounting.vendor-credits.post', $vendorCredit) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Post this vendor credit?', { type: 'action' })">
                            @csrf
                            <button type="submit" class="btn btn-cta">{{ __('Post Vendor Credit') }}</button>
                        </form>
                    @endif
                    @if($vendorCredit->status === 'posted' && $vendorCredit->available > 0)
                        <a href="{{ route('accounting.vendor-credits.apply-form', $vendorCredit) }}" class="btn btn-sec">{{ __('Apply Credit') }}</a>
                    @endif
                    @if(!in_array($vendorCredit->status, ['void', 'applied']))
                        @can('vendor-credits.void')
                            <form method="POST" action="{{ route('accounting.vendor-credits.void', $vendorCredit) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Are you sure you want to void this vendor credit?') }}', { type: 'danger' })">
                                @csrf
                                <button type="submit" class="btn btn-danger">{{ __('Void') }}</button>
                            </form>
                        @endcan
                    @endif
                    <button type="button" onclick="window.print()" class="btn btn-ghost">{{ __('Print') }}</button>
                    <a href="{{ route('accounting.vendor-credits.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <x-input-error :messages="$errors->get('error')" class="mb-4" />

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    {{-- profile header --}}
                    <section class="card">
                        <div class="prof">
                            <span class="ava-xl"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div>
                                <div class="n">{{ __('Vendor Credit') }} {{ $vendorCredit->credit_note_number }} <span class="badge b-{{ $statusCls }}"><span class="bdot"></span>{{ $statusLabel }}</span></div>
                                <div class="c">
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $vendorCredit->vendor->name ?? '—' }}</span>
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ __('Date') }} · {{ $vendorCredit->credit_note_date?->format('M d, Y') ?? '—' }}</span>
                                    @if($vendorCredit->bill)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>{{ $vendorCredit->bill->bill_number }}</span>
                                    @endif
                                    @if($vendorCredit->reference)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>{{ $vendorCredit->reference }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="acts">
                                @if($vendorCredit->status === 'draft')
                                    <form method="POST" action="{{ route('accounting.vendor-credits.post', $vendorCredit) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Post this vendor credit?', { type: 'action' })">
                                        @csrf
                                        <button type="submit" class="btn btn-cta btn-sm">{{ __('Post') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </section>

                    {{-- stat grid --}}
                    <div class="statgrid">
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v10M15 9.5c-.6-1-1.7-1.5-3-1.5-1.8 0-3 .9-3 2.2 0 2.8 6 1.6 6 4.3 0 1.3-1.2 2.2-3 2.2-1.3 0-2.4-.5-3-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Total') }} ({{ $cs }})</div><div class="v">{{ format_number($vcTotal) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div><div class="l">{{ __('Applied') }} ({{ $cs }})</div><div class="v mint">{{ format_number($vcApplied) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Available') }} ({{ $cs }})</div><div class="v">{{ format_number($vcAvailable) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div><div class="l">{{ __('Tax') }} ({{ $cs }})</div><div class="v">{{ format_number($vcTax) }}</div></div></div>
                    </div>

                    {{-- tabs --}}
                    <section class="card">
                        <div class="card-sec" style="padding-bottom:8px">
                            <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Contents') }}</h2><span class="rule"></span></div>
                            <div class="tabs" role="tablist">
                                <button type="button" class="tab on" data-target="tab-overview" role="tab">{{ __('Overview') }}</button>
                                <button type="button" class="tab" data-target="tab-allocations" role="tab">{{ __('Allocations') }}</button>
                                <button type="button" class="tab" data-target="tab-lines" role="tab">{{ __('Line Items') }}</button>
                            </div>

                            <div class="tab-panel" id="tab-overview">
                                <div class="g4">
                                    <div class="field"><label>{{ __('Vendor') }}</label><a href="{{ route('accounting.vendors.show', $vendorCredit->vendor) }}" style="font-weight:600;color:var(--sec,#128F8E)">{{ $vendorCredit->vendor->name ?? '—' }}</a></div>
                                    <div class="field"><label>{{ __('Credit Date') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $vendorCredit->credit_note_date?->format('M d, Y') ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Reference') }}</label><span class="mono">{{ $vendorCredit->reference ?? '—' }}</span></div>
                                    @if($vendorCredit->bill)
                                        <div class="field"><label>{{ __('Reference Bill') }}</label><a href="{{ route('accounting.bills.show', $vendorCredit->bill) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $vendorCredit->bill->bill_number }}</a></div>
                                    @endif
                                    <div class="field"><label>{{ __('Created By') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $vendorCredit->createdBy?->name ?? '—' }}</span></div>
                                    @if ($vendorCredit->journalEntry)
                                        <div class="field"><label>{{ __('Journal Entry') }}</label><a href="{{ route('accounting.journal-entries.show', $vendorCredit->journalEntry) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $vendorCredit->journalEntry->reference ?? ('JE-' . str_pad($vendorCredit->journalEntry->id, 4, '0', STR_PAD_LEFT)) }}</a></div>
                                    @endif
                                    @if($vendorCredit->void_reason)
                                        <div class="field sp2"><label>{{ __('Void Reason') }}</label><span class="em" style="font-size:.8125rem;color:var(--red,#DC2626)">{{ $vendorCredit->void_reason }}</span></div>
                                    @endif
                                    @if ($vendorCredit->memo)
                                        <div class="field sp2"><label>{{ __('Description') }}</label><span class="em" style="font-size:.8125rem">{{ $vendorCredit->memo }}</span></div>
                                    @endif
                                </div>
                            </div>

                            <div class="tab-panel" id="tab-allocations" style="display:none">
                                <div class="li-wrap">
                                    <table>
                                        <thead><tr>
                                            <th style="width:22%">{{ __('Date') }}</th>
                                            <th style="width:32%">{{ __('Bill') }}</th>
                                            <th class="num" style="width:23%">{{ __('Amount') }} ({{ $cs }})</th>
                                            <th style="width:23%">{{ __('Status') }}</th>
                                        </tr></thead>
                                        <tbody>
                                            @forelse($allocations as $allocation)
                                                <tr>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $vendorCredit->credit_note_date?->format('M d, Y') ?? '—' }}</td>
                                                    <td><a href="{{ route('accounting.bills.show', $allocation->bill) }}" class="link">{{ $allocation->bill->bill_number ?? '—' }}</a></td>
                                                    <td class="numr">{{ format_number($allocation->amount) }}</td>
                                                    <td><span class="badge b-act"><span class="bdot"></span>{{ __('Applied') }}</span></td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="em" style="padding:22px;text-align:center">{{ __('No allocations yet.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-panel" id="tab-lines" style="display:none">
                                <div class="li-wrap">
                                    <table>
                                        <thead><tr>
                                            <th style="width:10%">{{ __('Item Code') }}</th>
                                            <th style="width:20%">{{ __('Item') }}</th>
                                            <th style="width:26%">{{ __('Description') }}</th>
                                            <th class="num" style="width:6%">{{ __('Qty') }}</th>
                                            <th class="num" style="width:10%">{{ __('Unit Price') }}</th>
                                            <th class="num" style="width:6%">{{ __('Disc %') }}</th>
                                            <th class="num" style="width:6%">{{ __('Tax %') }}</th>
                                            <th class="num" style="width:12%">{{ __('Amount') }}</th>
                                        </tr></thead>
                                        <tbody>
                                            @forelse($vendorCredit->lines as $line)
                                                <tr>
                                                    <td class="mono em">{{ $line->product?->sku ?? '—' }}</td>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $line->product?->name ?? '—' }}</td>
                                                    <td class="em">{{ $line->description }}</td>
                                                    <td class="numr">{{ number_format((float) $line->quantity, 2) }}</td>
                                                    <td class="numr">{{ format_number((float) $line->unit_price) }}</td>
                                                    <td class="numr">{{ $line->discount }}%</td>
                                                    <td class="numr">{{ $line->tax_rate }}%</td>
                                                    <td class="numr" style="font-weight:800">{{ format_number((float) $line->line_total) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="em" style="padding:22px;text-align:center">{{ __('No line items on this vendor credit.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="li-totals" style="margin-top:12px"><div class="box">
                                    <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v">{{ format_number($vcSubtotal) }}</span></div>
                                    <div class="trow"><span>{{ __('Tax') }}</span><span class="v">{{ format_number($vcTax) }}</span></div>
                                    <div class="trow total"><span>{{ __('Total') }}</span><span class="v">{{ format_number($vcTotal) }}</span></div>
                                </div></div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7.5h8M8.5 12h.01M12 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Summary') }}</h2></div>
                            <div style="margin-top:8px">
                                <div class="srow"><span class="l">{{ __('Subtotal') }}</span><span class="v">{{ format_number($vcSubtotal) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Tax') }}</span><span class="v">{{ format_number($vcTax) }}</span></div>
                                <div class="srow strong"><span class="l">{{ __('Total') }}</span><span class="v">{{ format_number($vcTotal) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Applied') }}</span><span class="v mint">{{ format_number($vcApplied) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Available') }}</span><span class="v">{{ format_number($vcAvailable) }}</span></div>
                            </div>
                            <div class="gt"><span class="l">{{ __('Available') }}</span><span class="v">{{ $cs }}{{ format_number($vcAvailable) }}</span></div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                <button type="button" onclick="window.print()" class="vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Print') }}</button>
                                @if($vendorCredit->status === 'posted' && $vendorCredit->available > 0)
                                    <a href="{{ route('accounting.vendor-credits.apply-form', $vendorCredit) }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Apply Credit') }}</a>
                                @endif
                                @if($vendorCredit->vendor)
                                    <a href="{{ route('accounting.vendors.show', $vendorCredit->vendor) }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('View Vendor') }}</a>
                                @endif
                                <a href="{{ route('accounting.vendor-credits.create') }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('New Vendor Credit') }}</a>
                                <a href="{{ route('accounting.vendor-credits.index') }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('All Vendor Credits') }}</a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.suite .tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.suite .tab').forEach(t => t.classList.remove('on'));
                tab.classList.add('on');
                document.querySelectorAll('.suite .tab-panel').forEach(p => {
                    p.style.display = (p.id === tab.dataset.target) ? '' : 'none';
                });
            });
        });
    </script>
</x-app-layout>
