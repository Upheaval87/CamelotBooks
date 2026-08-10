<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $paidTotal = (float) $payment->amount;
        $allocatedTotal = $payment->allocations->sum(fn($a) => (float) $a->amount);
        $unallocated = round($paidTotal - $allocatedTotal, 2);
        $methodLabel = ucfirst(str_replace('_', ' ', $payment->payment_method ?? ''));
        $jeRef = $payment->journalEntry ? ($payment->journalEntry->reference ?? ('JE-' . str_pad($payment->journalEntry->id, 4, '0', STR_PAD_LEFT))) : null;
    @endphp

    <div class="suite py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Customer Payment') }} <span class="mono-chip">{{ $payment->payment_number }}</span></h1>
                    <div class="sub">{{ $payment->customer->name ?? '—' }} · {{ $payment->payment_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.customer-payments.create', ['customer_id' => $payment->customer_id]) }}" class="btn btn-sec">{{ __('New Payment') }}</a>
                    <button type="button" onclick="window.print()" class="btn btn-ghost">{{ __('Print') }}</button>
                    <a href="{{ route('accounting.customers.show', $payment->customer) }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    {{-- profile header --}}
                    <section class="card">
                        <div class="prof">
                            <span class="ava-xl"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M2 10h20M6 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div>
                                <div class="n">{{ __('Customer Payment') }} {{ $payment->payment_number }} <span class="badge b-act"><span class="bdot"></span>{{ __('Received') }}</span></div>
                                <div class="c">
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $payment->customer->name ?? '—' }}</span>
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</span>
                                    @if($methodLabel)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $methodLabel }}</span>
                                    @endif
                                    @if($payment->reference)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>{{ $payment->reference }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- stat grid --}}
                    <div class="statgrid">
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v10M15 9.5c-.6-1-1.7-1.5-3-1.5-1.8 0-3 .9-3 2.2 0 2.8 6 1.6 6 4.3 0 1.3-1.2 2.2-3 2.2-1.3 0-2.4-.5-3-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Amount') }} ({{ $cs }})</div><div class="v">{{ format_number($paidTotal) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div><div class="l">{{ __('Allocated') }} ({{ $cs }})</div><div class="v mint">{{ format_number($allocatedTotal) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Unallocated') }} ({{ $cs }})</div><div class="v">{{ format_number($unallocated) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/></svg></span>
                            <div><div class="l">{{ __('Method') }}</div><div class="v" style="font-size:.9rem">{{ $methodLabel ?: '—' }}</div></div></div>
                    </div>

                    {{-- tabs --}}
                    <section class="card">
                        <div class="card-sec" style="padding-bottom:8px">
                            <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Contents') }}</h2><span class="rule"></span></div>
                            <div class="tabs" role="tablist">
                                <button type="button" class="tab on" data-target="tab-overview" role="tab">{{ __('Overview') }}</button>
                                <button type="button" class="tab" data-target="tab-allocations" role="tab">{{ __('Allocations') }}</button>
                            </div>

                            <div class="tab-panel" id="tab-overview">
                                <div class="g4">
                                    <div class="field"><label>{{ __('Customer') }}</label><a href="{{ route('accounting.customers.show', $payment->customer) }}" style="font-weight:600;color:var(--sec,#128F8E)">{{ $payment->customer->name ?? '—' }}</a></div>
                                    <div class="field"><label>{{ __('Payment Date') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Payment Method') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $methodLabel ?: '—' }}</span></div>
                                    <div class="field"><label>{{ __('Bank Account') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $payment->bankAccount->name ?? '—' }}</span></div>
                                    @if($payment->reference)
                                        <div class="field"><label>{{ __('Reference') }}</label><span class="mono">{{ $payment->reference }}</span></div>
                                    @endif
                                    @if($payment->memo)
                                        <div class="field sp2"><label>{{ __('Description') }}</label><span class="em" style="font-size:.8125rem">{{ $payment->memo }}</span></div>
                                    @endif
                                    @if($jeRef)
                                        <div class="field"><label>{{ __('Journal Entry') }}</label><a href="{{ route('accounting.journal-entries.show', $payment->journalEntry) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $jeRef }}</a></div>
                                    @endif
                                    <div class="field"><label>{{ __('Created By') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $payment->createdBy?->name ?? '—' }}</span></div>
                                </div>
                            </div>

                            <div class="tab-panel" id="tab-allocations" style="display:none">
                                <div class="li-wrap">
                                    <table>
                                        <thead><tr>
                                            <th style="width:26%">{{ __('Invoice #') }}</th>
                                            <th style="width:24%">{{ __('Date') }}</th>
                                            <th class="num" style="width:25%">{{ __('Invoice Amount') }} ({{ $cs }})</th>
                                            <th class="num" style="width:25%">{{ __('Allocated') }} ({{ $cs }})</th>
                                        </tr></thead>
                                        <tbody>
                                            @forelse($payment->allocations as $allocation)
                                                <tr>
                                                    <td><a href="{{ route('accounting.invoices.show', $allocation->invoice) }}" class="link mono">{{ $allocation->invoice->invoice_number ?? '—' }}</a></td>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $allocation->invoice?->invoice_date?->format('M d, Y') ?? '—' }}</td>
                                                    <td class="numr">{{ format_number($allocation->invoice?->amount ?? 0) }}</td>
                                                    <td class="numr" style="font-weight:800">{{ format_number($allocation->amount) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="em" style="padding:22px;text-align:center">{{ __('No allocations on this payment.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="li-totals" style="margin-top:12px"><div class="box">
                                    <div class="trow"><span>{{ __('Total Allocated') }}</span><span class="v">{{ format_number($allocatedTotal) }}</span></div>
                                    <div class="trow total"><span>{{ __('Payment Amount') }}</span><span class="v">{{ format_number($paidTotal) }}</span></div>
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
                                <div class="srow"><span class="l">{{ __('Payment Amount') }}</span><span class="v">{{ format_number($paidTotal) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Allocated') }}</span><span class="v mint">{{ format_number($allocatedTotal) }}</span></div>
                                <div class="srow strong"><span class="l">{{ __('Unallocated') }}</span><span class="v">{{ format_number($unallocated) }}</span></div>
                            </div>
                            <div class="gt"><span class="l">{{ __('Total Received') }}</span><span class="v">{{ $cs }}{{ format_number($paidTotal) }}</span></div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                <button type="button" onclick="window.print()" class="vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Print') }}</button>
                                <a href="{{ route('accounting.customer-payments.create', ['customer_id' => $payment->customer_id]) }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('New Payment') }}</a>
                                <a href="{{ route('accounting.customers.show', $payment->customer) }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('View Customer') }}</a>
                                <a href="{{ route('accounting.customers.index') }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('All Customers') }}</a>
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
