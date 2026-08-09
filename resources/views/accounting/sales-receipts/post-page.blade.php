<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $balanced = abs((float) $totalDebits - (float) $totalCredits) < 0.01;
        $paymentMethods = $salesReceipt->payments->map(fn ($p) => $p->paymentMethod->name ?? '—')->unique()->implode(', ');
        $narration = __('Being receipt :num', ['num' => $salesReceipt->receipt_number]);
    @endphp

    <div class="sr-suite py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §5 sticky page head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Post Sales Receipt') }} <span class="mono-chip">{{ $salesReceipt->receipt_number }}</span></h1>
                    <div class="sub">{{ __('Posting writes this receipt to the ledger and locks it for editing.') }}</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.sales-receipts.show', $salesReceipt) }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
                    <form method="POST" action="{{ route('accounting.sales-receipts.post', $salesReceipt) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-cta">{{ __('Post Receipt') }} ✓</button>
                    </form>
                </div>
            </div>

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    {{-- §5 posting summary --}}
                    <section class="card">
                        <div class="card-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Posting Summary') }}</h2><span class="rule"></span></div>
                            <div class="ro-grid">
                                <div class="ro"><div class="l">{{ __('Receipt №') }}</div><div class="v mono">{{ $salesReceipt->receipt_number }}</div></div>
                                <div class="ro"><div class="l">{{ __('Customer') }}</div><div class="v">{{ $salesReceipt->customer->name ?? __('Walk-in') }}</div></div>
                                <div class="ro"><div class="l">{{ __('Status') }}</div><div class="v"><span class="badge b-draft"><span class="bdot"></span>{{ __('Draft') }}</span></div></div>
                                <div class="ro"><div class="l">{{ __('Receipt Date') }}</div><div class="v">{{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</div></div>
                                <div class="ro"><div class="l">{{ __('Method') }}</div><div class="v">{{ $paymentMethods ?: '—' }}</div></div>
                                <div class="ro"><div class="l">{{ __('Total') }}</div><div class="v">{{ $cs }}{{ format_number($salesReceipt->total) }}</div></div>
                            </div>
                        </div>
                    </section>

                    {{-- §5 journal preview --}}
                    <section class="card">
                        <div class="card-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Journal Entry Preview') }}</h2><span class="chip-t">{{ $balanced ? __('Balanced ✓') : __('Unbalanced!') }}</span><span class="rule"></span></div>
                            <div class="li-wrap">
                                <table>
                                    <thead><tr>
                                        <th style="width:14%">{{ __('Account Code') }}</th>
                                        <th style="width:46%">{{ __('Account') }}</th>
                                        <th class="num" style="width:20%">{{ __('Debit') }} ({{ $cs }})</th>
                                        <th class="num" style="width:20%">{{ __('Credit') }} ({{ $cs }})</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach($jeLines as $jl)
                                            @php $account = $accounts[$jl['account_id']] ?? null; @endphp
                                            <tr>
                                                <td class="mono">{{ $account->code ?? '—' }}</td>
                                                <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $account->name ?? '—' }}</td>
                                                <td class="numr">{{ $jl['debit'] ? format_number($jl['debit']) : '—' }}</td>
                                                <td class="numr">{{ $jl['credit'] ? format_number($jl['credit']) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="jfoot"><span>{{ __('Total Debits') }} {{ format_number($totalDebits) }}</span><span style="color:var(--faint,#8AA5A7)">=</span><span>{{ __('Total Credits') }} {{ format_number($totalCredits) }}</span></div>
                        </div>
                    </section>

                    {{-- §5 posting details --}}
                    <section class="card">
                        <div class="card-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Posting Details') }}</h2><span class="rule"></span></div>
                            <div class="g4">
                                <div class="field"><label>{{ __('Posting Date') }}</label><input type="date" class="input h44" value="{{ now()->format('Y-m-d') }}" disabled></div>
                                <div class="field"><label>{{ __('Period') }}</label><select class="input h44" disabled><option>{{ $salesReceipt->receipt_date?->format('M Y') ?? now()->format('M Y') }}</option></select></div>
                                <div class="field"><label>{{ __('Posted By') }}</label><input type="text" class="input h44" value="{{ auth()->user()?->name ?? '' }}" disabled></div>
                                <div class="field"><label>{{ __('Narration') }}</label><input type="text" class="input h44" value="{{ $narration }}" disabled></div>
                            </div>
                            <div class="note-info"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex:none;margin-top:2px" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                {{ __('Posting locks the receipt against edits. To amend afterwards you will need to void and re-issue it per your audit trail settings.') }}</div>
                        </div>
                    </section>
                </div>

                {{-- §5 rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 20V11M10.5 20V5M16 20v-7M21 20H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><h2>{{ __('Ledger Impact') }}</h2></div>
                            <div style="margin-top:8px">
                                @foreach($jeLines as $jl)
                                    @php $account = $accounts[$jl['account_id']] ?? null; @endphp
                                    <div class="srow"><span class="l">{{ ($account->name ?? __('Account')) }} <span class="mono" style="color:var(--faint,#8AA5A7)">{{ $account->code ?? '' }}</span></span><span class="v">+{{ format_number($jl['debit'] ?: $jl['credit']) }}</span></div>
                                @endforeach
                            </div>
                            <div class="gt"><span class="l">{{ __('Entry Total') }}</span><span class="v">{{ $cs }}{{ format_number($totalDebits) }}</span></div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                <a href="{{ route('accounting.sales-receipts.show', $salesReceipt) }}" class="vitem"><span class="ic">←</span>{{ __('Back to Receipt') }}</a>
                                <a href="{{ route('accounting.sales-receipts.index') }}" class="vitem"><span class="ic">📒</span>{{ __('Receipts List') }}</a>
                                <a href="{{ route('accounting.reports.sales-receipts.daily-summary') }}" class="vitem"><span class="ic">📊</span>{{ __('Daily Summary') }}</a>
                                <a href="{{ route('accounting.reports.sales-receipts.cashbook') }}" class="vitem"><span class="ic">📒</span>{{ __('Cashbook') }}</a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
