<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $received = $salesReceipt->payments->sum('amount');
        $unallocated = max($salesReceipt->total - $received, 0);
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §4 sticky page head --}}
            <div class="q2-head q2-head--sticky">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="q2-title" style="font-size:1.375rem">{{ __('Sales Receipt') }} <span class="q2-mono">{{ $salesReceipt->receipt_number }}</span></h1>
                        <span class="q2-badge q2-badge--{{ $salesReceipt->status }}">
                            @switch($salesReceipt->status)
                                @case('draft') <span class="q2-dot"></span>{{ __('Draft') }} @break
                                @case('posted') <span class="q2-dot"></span>{{ __('Posted') }} @break
                                @case('voided') <span class="q2-dot"></span>{{ __('Voided') }} @break
                            @endswitch
                        </span>
                    </div>
                    <p class="q2-sub">{{ $salesReceipt->customer->name ?? __('Walk-in') }} · {{ __('received') }} {{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div class="q2-head-actions">
                    @if($salesReceipt->status === 'draft')
                        <a href="{{ route('accounting.sales-receipts.edit', $salesReceipt) }}" class="q2-btn q2-btn--sec">{{ __('Edit') }}</a>
                        @can('sales-receipts.post')
                            <a href="{{ route('accounting.sales-receipts.post-page', $salesReceipt) }}" class="q2-btn q2-btn--cta">{{ __('Post Receipt') }}</a>
                        @endcan
                    @endif
                    @if($salesReceipt->status === 'posted')
                        @if($salesReceipt->customer && $salesReceipt->customer->email)
                            <form method="POST" action="{{ route('accounting.sales-receipts.email', $salesReceipt) }}" class="inline">
                                @csrf
                                <button type="submit" class="q2-btn q2-btn--ghost">{{ __('Email Receipt') }}</button>
                            </form>
                        @endif
                        @can('sales-receipts.void')
                            <form method="POST" action="{{ route('accounting.sales-receipts.void', $salesReceipt) }}" class="inline" onsubmit="return fbPromptForm(event, '{{ __('Enter void reason') }}:')">
                                @csrf<input type="hidden" name="void_reason" value="" />
                                <button type="submit" class="q2-btn q2-btn--danger">{{ __('Void Receipt') }}</button>
                            </form>
                        @endcan
                    @endif
                    <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" class="q2-btn q2-btn--ghost">{{ __('Print') }}</a>
                    <a href="{{ route('accounting.sales-receipts.index') }}" class="q2-btn q2-btn--ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">

                    {{-- §4 profile header --}}
                    <div class="q2-prof">
                        <div class="q2-pbar">
                            <div class="q2-pid">
                                <span class="q2-pic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                                <div>
                                    <div class="q2-plabel">{{ __('Receipt') }} №</div>
                                    <div class="q2-pname">{{ $salesReceipt->receipt_number }}</div>
                                    <div class="q2-pmeta">
                                        <span class="q2-cchip"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $salesReceipt->customer->name ?? __('Walk-in') }}</span>
                                        <span class="q2-cchip">{{ __('Date') }} · {{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Branch') }} · {{ $salesReceipt->branch->name ?? '—' }}</span>
                                        @if($salesReceipt->reference)
                                            <span class="q2-cchip">{{ __('Reference') }} · {{ $salesReceipt->reference }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="q2-pacts">
                                <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" class="q2-btn q2-btn--ghost q2-btn--sm">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ __('Print / PDF') }}
                                </a>
                                @if($salesReceipt->status === 'draft')
                                    <a href="{{ route('accounting.sales-receipts.edit', $salesReceipt) }}" class="q2-btn q2-btn--soft q2-btn--sm">{{ __('Edit') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- §4 stat grid --}}
                    <div class="q2-statgrid">
                        <div class="q2-stat">
                            <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v10M15 9.5c-.6-1-1.7-1.5-3-1.5-1.8 0-3 .9-3 2.2 0 2.8 6 1.6 6 4.3 0 1.3-1.2 2.2-3 2.2-1.3 0-2.4-.5-3-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div class="q2-stat-meta">
                                <span class="q2-stat-lbl">{{ __('Total') }}</span>
                                <span class="q2-stat-val">{{ format_number($salesReceipt->total) }}</span>
                                <span class="q2-stat-var">{{ $cs }}</span>
                            </div>
                        </div>
                        <div class="q2-stat">
                            <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div class="q2-stat-meta">
                                <span class="q2-stat-lbl">{{ __('Received') }}</span>
                                <span class="q2-stat-val q2-stat-val--mint">{{ format_number($received) }}</span>
                                <span class="q2-stat-var">{{ $cs }}</span>
                            </div>
                        </div>
                        <div class="q2-stat">
                            <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div class="q2-stat-meta">
                                <span class="q2-stat-lbl">{{ __('Unallocated') }}</span>
                                <span class="q2-stat-val">{{ format_number($unallocated) }}</span>
                                <span class="q2-stat-var">{{ $cs }}</span>
                            </div>
                        </div>
                        <div class="q2-stat">
                            <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div class="q2-stat-meta">
                                <span class="q2-stat-lbl">{{ __('Tax') }}</span>
                                <span class="q2-stat-val">{{ format_number($salesReceipt->tax_total) }}</span>
                                <span class="q2-stat-var">{{ $cs }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- §4 tabs --}}
                    <div class="q2-tabs" role="tablist">
                        <button type="button" class="q2-tab is-active" data-target="tab-overview" role="tab">{{ __('Overview') }}</button>
                        <button type="button" class="q2-tab" data-target="tab-payments" role="tab">{{ __('Payments') }}</button>
                        <button type="button" class="q2-tab" data-target="tab-lines" role="tab">{{ __('Line Items') }}</button>
                    </div>

                    <div class="q2-tdiv">
                        {{-- overview tab --}}
                        <section id="tab-overview" class="q2-tab-panel">
                            <div class="q2-sec">
                                <div class="q2-sec-head">
                                    <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <h2 class="q2-sec-title">{{ __('Receipt Details') }}</h2>
                                </div>
                                <div class="q2-g4 mt-5">
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Receipt Number') }}</span>
                                        <span class="q2-amt q2-mono">{{ $salesReceipt->receipt_number }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Customer') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $salesReceipt->customer->name ?? __('Walk-in') }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Date') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $salesReceipt->receipt_date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Reference') }}</span>
                                        <span class="q2-amt q2-mono">{{ $salesReceipt->reference ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Branch') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $salesReceipt->branch->name ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Cost Center') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $salesReceipt->costCenter->name ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Created By') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $salesReceipt->createdByUser->name ?? '—' }}</span>
                                    </div>
                                    @if($salesReceipt->journal_entry_id)
                                        <div class="q2-field">
                                            <span class="q2-label">{{ __('Journal Entry') }}</span>
                                            <a href="{{ route('accounting.journal-entries.show', $salesReceipt->journal_entry_id) }}" class="q2-amt q2-link">{{ __('JE-') }}{{ str_pad($salesReceipt->journal_entry_id, 4, '0', STR_PAD_LEFT) }}</a>
                                        </div>
                                    @endif
                                    @if($salesReceipt->memo)
                                        <div class="q2-field" style="grid-column: span 2">
                                            <span class="q2-label">{{ __('Description') }}</span>
                                            <p class="q2-rail-memo" style="font-size:.8125rem;color:var(--muted,#5F7476)">{{ $salesReceipt->memo }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($salesReceipt->status === 'voided' && $salesReceipt->void_reason)
                                <div class="q2-note-info mt-4">{{ __('Voided') }}: {{ $salesReceipt->void_reason }}</div>
                            @endif
                        </section>

                        {{-- payments tab --}}
                        <section id="tab-payments" class="q2-tab-panel">
                            <div class="q2-card q2-card--list">
                                <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                                    <table class="q2-tbl">
                                        <thead><tr>
                                            <th>{{ __('Method') }}</th>
                                            <th>{{ __('Reference') }}</th>
                                            <th class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                            <th class="q2-right">{{ __('Cash Tendered') }} ({{ $cs }})</th>
                                            <th class="q2-right">{{ __('Change') }} ({{ $cs }})</th>
                                        </tr></thead>
                                        <tbody>
                                            @foreach($salesReceipt->payments as $payment)
                                                <tr>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $payment->paymentMethod->name ?? '—' }}</td>
                                                    <td class="q2-amt q2-mono">{{ $payment->reference_number ?? '—' }}</td>
                                                    <td class="q2-right q2-amt">{{ format_number($payment->amount) }}</td>
                                                    <td class="q2-right q2-amt">{{ $payment->cash_tendered ? format_number($payment->cash_tendered) : '—' }}</td>
                                                    <td class="q2-right q2-amt">{{ $payment->change_given ? format_number($payment->change_given) : '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex justify-end mt-4 px-5 pb-5">
                                    <div class="q2-railsum" style="width:16rem">
                                        <div class="q2-srow"><span>{{ __('Received') }}</span><span class="q2-sval">{{ format_number($received) }}</span></div>
                                        <div class="q2-srow"><span>{{ __('Unallocated') }}</span><span class="q2-sval">{{ format_number($unallocated) }}</span></div>
                                        <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval">{{ format_number($salesReceipt->total) }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- line items tab --}}
                        <section id="tab-lines" class="q2-tab-panel">
                            <div class="q2-card q2-card--list">
                                <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                                    <table class="q2-tbl">
                                        <thead><tr>
                                            <th>{{ __('Product') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th class="q2-right">{{ __('Qty') }}</th>
                                            <th class="q2-right">{{ __('Unit Price') }} ({{ $cs }})</th>
                                            <th class="q2-right">{{ __('Discount') }}</th>
                                            <th class="q2-right">{{ __('Tax') }} ({{ $cs }})</th>
                                            <th class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                                        </tr></thead>
                                        <tbody>
                                            @foreach($salesReceipt->lines as $line)
                                                <tr>
                                                    <td>{{ $line->product->name ?? '—' }}</td>
                                                    <td>{{ $line->description }}</td>
                                                    <td class="q2-right">{{ number_format($line->quantity, 2) }}</td>
                                                    <td class="q2-right q2-amt">{{ format_number($line->unit_price) }}</td>
                                                    <td class="q2-right">{{ number_format($line->discount, 2) }}</td>
                                                    <td class="q2-right q2-amt">{{ format_number($line->tax_amount) }}</td>
                                                    <td class="q2-right q2-amt" style="font-weight:800">{{ format_number($line->line_total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex justify-end mt-4 px-5 pb-5">
                                    <div class="q2-railsum" style="width:16rem">
                                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval">{{ format_number($salesReceipt->subtotal) }}</span></div>
                                        <div class="q2-srow"><span>{{ __('Tax') }}</span><span class="q2-sval">{{ format_number($salesReceipt->tax_total) }}</span></div>
                                        <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval">{{ format_number($salesReceipt->total) }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                {{-- §4 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Actions') }}</div>
                        <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Print / PDF') }}
                        </a>
                        @if($salesReceipt->status === 'draft')
                            <a href="{{ route('accounting.sales-receipts.edit', $salesReceipt) }}" class="q2-vitem">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Edit Receipt') }}
                            </a>
                            @can('sales-receipts.post')
                                <a href="{{ route('accounting.sales-receipts.post-page', $salesReceipt) }}" class="q2-vitem">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 17V7l14 5-14 5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ __('Post Receipt') }}
                                </a>
                            @endcan
                        @endif
                        @if($salesReceipt->status === 'posted' && $salesReceipt->customer && $salesReceipt->customer->email)
                            <form method="POST" action="{{ route('accounting.sales-receipts.email', $salesReceipt) }}" class="inline">
                                @csrf
                                <button type="submit" class="q2-vitem" style="width:100%">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18v12H3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M3 7l9 6 9-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ __('Email Receipt') }}
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('accounting.sales-receipts.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('All Receipts') }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.q2-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.q2-tab').forEach(t => t.classList.remove('is-active'));
                tab.classList.add('is-active');
                document.querySelectorAll('.q2-tab-panel').forEach(p => {
                    p.style.display = (p.id === tab.dataset.target) ? '' : 'none';
                });
            });
        });
    </script>
</x-app-layout>
