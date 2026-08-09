<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $balanced = abs((float) $totalDebits - (float) $totalCredits) < 0.01;
        $paymentMethods = $salesReceipt->payments->map(fn ($p) => $p->paymentMethod->name ?? '—')->unique()->implode(', ');
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §5 sticky page head --}}
            <div class="q2-head q2-head--sticky">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="q2-title" style="font-size:1.375rem">{{ __('Post Sales Receipt') }} <span class="q2-mono-chip">{{ $salesReceipt->receipt_number }}</span></h1>
                    </div>
                    <p class="q2-sub">{{ __('Posting writes this receipt to the ledger and locks it for editing.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.sales-receipts.show', $salesReceipt) }}" class="q2-btn q2-btn--ghost">{{ __('Cancel') }}</a>
                    <form method="POST" action="{{ route('accounting.sales-receipts.post', $salesReceipt) }}" class="inline">
                        @csrf
                        <button type="submit" class="q2-btn q2-btn--cta">{{ __('Post Receipt') }}</button>
                    </form>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">

                    {{-- §5 posting summary --}}
                    <div class="q2-sec">
                        <div class="q2-sec-head">
                            <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <h2 class="q2-sec-title">{{ __('Posting Summary') }}</h2>
                        </div>
                        <div class="q2-ro-grid mt-5">
                            <div class="q2-ro">
                                <div class="q2-ro-lbl">{{ __('Receipt №') }}</div>
                                <div class="q2-ro-val q2-mono">{{ $salesReceipt->receipt_number }}</div>
                            </div>
                            <div class="q2-ro">
                                <div class="q2-ro-lbl">{{ __('Customer') }}</div>
                                <div class="q2-ro-val" style="font-weight:600">{{ $salesReceipt->customer->name ?? __('Walk-in') }}</div>
                            </div>
                            <div class="q2-ro">
                                <div class="q2-ro-lbl">{{ __('Status') }}</div>
                                <div class="q2-ro-val"><span class="q2-badge q2-badge--draft"><span class="q2-dot"></span>{{ __('Draft') }}</span></div>
                            </div>
                            <div class="q2-ro">
                                <div class="q2-ro-lbl">{{ __('Receipt Date') }}</div>
                                <div class="q2-ro-val" style="font-weight:600">{{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</div>
                            </div>
                            <div class="q2-ro">
                                <div class="q2-ro-lbl">{{ __('Method') }}</div>
                                <div class="q2-ro-val" style="font-weight:600">{{ $paymentMethods ?: '—' }}</div>
                            </div>
                            <div class="q2-ro">
                                <div class="q2-ro-lbl">{{ __('Total') }}</div>
                                <div class="q2-ro-val" style="font-weight:800">{{ $cs }}{{ format_number($salesReceipt->total) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- §5 journal preview --}}
                    <div class="q2-sec">
                        <div class="q2-sec-head">
                            <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <h2 class="q2-sec-title">{{ __('Journal Entry Preview') }}</h2>
                            <span class="q2-chip-t">{{ $balanced ? __('Balanced ✓') : __('Unbalanced!') }}</span>
                        </div>
                        <div class="q2-card q2-card--list mt-4">
                            <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                                <table class="q2-tbl">
                                    <thead><tr>
                                        <th>{{ __('Account Code') }}</th>
                                        <th>{{ __('Account') }}</th>
                                        <th class="q2-right">{{ __('Debit') }} ({{ $cs }})</th>
                                        <th class="q2-right">{{ __('Credit') }} ({{ $cs }})</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach($jeLines as $jl)
                                            @php $account = $accounts[$jl['account_id']] ?? null; @endphp
                                            <tr>
                                                <td class="q2-mono">{{ $account->code ?? '—' }}</td>
                                                <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $account->name ?? '—' }}</td>
                                                <td class="q2-right q2-amt">{{ $jl['debit'] ? format_number($jl['debit']) : '—' }}</td>
                                                <td class="q2-right q2-amt">{{ $jl['credit'] ? format_number($jl['credit']) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="q2-jfoot">
                                <span>{{ __('Total Debits') }} {{ format_number($totalDebits) }}</span>
                                <span style="color:var(--faint,#8AA5A7)">=</span>
                                <span>{{ __('Total Credits') }} {{ format_number($totalCredits) }}</span>
                            </div>
                        </div>
                        @if(!$balanced)
                            <div class="q2-note-info mt-4">{{ __('This journal entry is not balanced. Check the receipt lines and payments before posting.') }}</div>
                        @endif
                    </div>
                </div>

                {{-- §5 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Receipt Summary') }}</div>
                        <div class="q2-railsum" style="padding:0 4px">
                            <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval">{{ format_number($salesReceipt->subtotal) }}</span></div>
                            <div class="q2-srow"><span>{{ __('Tax') }}</span><span class="q2-sval">{{ format_number($salesReceipt->tax_total) }}</span></div>
                            <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval">{{ format_number($salesReceipt->total) }}</span></div>
                        </div>
                        <div class="q2-rule"></div>
                        <a href="{{ route('accounting.sales-receipts.show', $salesReceipt) }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Back to Receipt') }}
                        </a>
                        <a href="{{ route('accounting.sales-receipts.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('All Receipts') }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
