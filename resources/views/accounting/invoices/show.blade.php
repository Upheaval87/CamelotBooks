<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $invSubtotal = $invoice->lines->sum(fn ($l) => (float) $l->amount);
        $invTax = $invoice->lines->sum(fn ($l) => (float) $l->tax_amount);
        $invTotal = $invSubtotal + $invTax;
        $invPaid = (float) $invoice->amount_paid;
        $invBalance = max($invTotal - $invPaid, 0);
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §4 sticky page head --}}
            <div class="q2-head q2-head--sticky">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="q2-title" style="font-size:1.375rem">{{ __('Invoice') }} <span class="q2-mono">{{ $invoice->invoice_number }}</span></h1>
                        <span class="q2-badge q2-badge--{{ $invoice->status }}">
                            @switch($invoice->status)
                                @case('draft') <span class="q2-dot"></span>{{ __('Draft') }} @break
                                @case('sent') <span class="q2-dot"></span>{{ __('Sent') }} @break
                                @case('partially_paid') <span class="q2-dot"></span>{{ __('Partially Paid') }} @break
                                @case('paid') <span class="q2-dot"></span>{{ __('Paid') }} @break
                                @case('overdue') <span class="q2-dot"></span>{{ __('Overdue') }} @break
                                @case('void') <span class="q2-dot"></span>{{ __('Void') }} @break
                            @endswitch
                        </span>
                    </div>
                    <p class="q2-sub">{{ $invoice->customer->name ?? __('—') }} · {{ __('issued') }} {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}
                        @if ($invoice->due_date) · {{ __('due') }} {{ $invoice->due_date->format('M d, Y') }} @endif
                    </p>
                </div>
                <div class="q2-head-actions">
                    @if($invoice->status === 'draft')
                        <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="q2-btn q2-btn--sec">{{ __('Edit') }}</a>
                        @can('invoices.post')
                            <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Post this invoice?', { type: 'action' })">
                                @csrf
                                <button type="submit" class="q2-btn q2-btn--cta">{{ __('Post Invoice') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                        @can('invoices.void')
                            <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Are you sure you want to void this invoice?') }}', { type: 'danger' })">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="q2-btn q2-btn--danger">{{ __('Void Invoice') }}</button>
                            </form>
                        @endcan
                    @endif
                    <a href="{{ route('accounting.invoices.print', $invoice) }}" target="_blank" class="q2-btn q2-btn--ghost">{{ __('Print') }}</a>
                    <a href="{{ route('accounting.invoices.index') }}" class="q2-btn q2-btn--ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <x-input-error :messages="$errors->get('error')" class="mb-4" />

            <div class="q2-shell">
                <div class="q2-main">

                    {{-- §4 profile header --}}
                    <div class="q2-prof">
                        <div class="q2-pbar">
                            <div class="q2-pid">
                                <span class="q2-pic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                                <div>
                                    <div class="q2-plabel">{{ __('Invoice') }} №</div>
                                    <div class="q2-pname">{{ $invoice->invoice_number }}</div>
                                    <div class="q2-pmeta">
                                        <span class="q2-cchip"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $invoice->customer->name ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Date') }} · {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</span>
                                        @if ($invoice->due_date)
                                            <span class="q2-cchip">{{ __('Due') }} · {{ $invoice->due_date->format('M d, Y') }}</span>
                                        @endif
                                        @if($invoice->reference)
                                            <span class="q2-cchip">{{ __('Reference') }} · {{ $invoice->reference }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="q2-pacts">
                                <a href="{{ route('accounting.invoices.print', $invoice) }}" target="_blank" class="q2-btn q2-btn--ghost q2-btn--sm">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ __('Print / PDF') }}
                                </a>
                                @if($invoice->status === 'draft')
                                    <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="q2-btn q2-btn--soft q2-btn--sm">{{ __('Edit') }}</a>
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
                                <span class="q2-stat-val">{{ format_number($invTotal) }}</span>
                                <span class="q2-stat-var">{{ $cs }}</span>
                            </div>
                        </div>
                        <div class="q2-stat">
                            <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div class="q2-stat-meta">
                                <span class="q2-stat-lbl">{{ __('Paid') }}</span>
                                <span class="q2-stat-val q2-stat-val--mint">{{ format_number($invPaid) }}</span>
                                <span class="q2-stat-var">{{ $cs }}</span>
                            </div>
                        </div>
                        <div class="q2-stat">
                            <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div class="q2-stat-meta">
                                <span class="q2-stat-lbl">{{ __('Balance Due') }}</span>
                                <span class="q2-stat-val @if($invBalance > 0 && $invoice->status === 'overdue') q2-stat-val--red @endif">{{ format_number($invBalance) }}</span>
                                <span class="q2-stat-var">{{ $cs }}</span>
                            </div>
                        </div>
                        <div class="q2-stat">
                            <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div class="q2-stat-meta">
                                <span class="q2-stat-lbl">{{ __('Tax') }}</span>
                                <span class="q2-stat-val">{{ format_number($invTax) }}</span>
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
                                    <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <h2 class="q2-sec-title">{{ __('Invoice Details') }}</h2>
                                </div>
                                <div class="q2-g4 mt-5">
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Customer') }}</span>
                                        <a href="{{ route('accounting.customers.show', $invoice->customer) }}" class="q2-amt q2-link" style="font-weight:600">{{ $invoice->customer->name ?? '—' }}</a>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Invoice Date') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Due Date') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Reference') }}</span>
                                        <span class="q2-amt q2-mono">{{ $invoice->reference ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Created By') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $invoice->createdBy?->name ?? '—' }}</span>
                                    </div>
                                    @if ($invoice->journalEntry)
                                        <div class="q2-field">
                                            <span class="q2-label">{{ __('Journal Entry') }}</span>
                                            <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="q2-amt q2-link q2-mono">{{ $invoice->journalEntry->reference ?? ('JE-' . str_pad($invoice->journalEntry->id, 4, '0', STR_PAD_LEFT)) }}</a>
                                        </div>
                                    @endif
                                    @if ($invoice->memo)
                                        <div class="q2-field" style="grid-column: span 2">
                                            <span class="q2-label">{{ __('Description') }}</span>
                                            <p class="q2-rail-memo" style="font-size:.8125rem;color:var(--muted,#5F7476)">{{ $invoice->memo }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </section>

                        {{-- payments tab --}}
                        <section id="tab-payments" class="q2-tab-panel">
                            <div class="q2-card q2-card--list">
                                <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                                    <table class="q2-tbl">
                                        <thead><tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Reference') }}</th>
                                            <th class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                            <th>{{ __('Method') }}</th>
                                        </tr></thead>
                                        <tbody>
                                            @forelse($payments as $payment)
                                                <tr>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                                    <td class="q2-amt q2-mono">{{ $payment->reference ?? '—' }}</td>
                                                    <td class="q2-right q2-amt">{{ format_number($payment->pivot->amount) }}</td>
                                                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="q2-empty">{{ __('No payments recorded yet.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($payments->isNotEmpty())
                                    <div class="flex justify-end mt-4 px-5 pb-5">
                                        <div class="q2-railsum" style="width:16rem">
                                            <div class="q2-srow"><span>{{ __('Paid') }}</span><span class="q2-sval">{{ format_number($invPaid) }}</span></div>
                                            <div class="q2-srow"><span>{{ __('Balance Due') }}</span><span class="q2-sval">{{ format_number($invBalance) }}</span></div>
                                            <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval">{{ format_number($invTotal) }}</span></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>

                        {{-- line items tab --}}
                        <section id="tab-lines" class="q2-tab-panel">
                            <div class="q2-card q2-card--list">
                                <div class="q2-tbl-wrap" style="border:none;border-radius:0">
                                    <table class="q2-tbl">
                                        <thead><tr>
                                            <th>{{ __('Item Code') }}</th>
                                            <th>{{ __('Item') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th class="q2-right">{{ __('Qty') }}</th>
                                            <th class="q2-right">{{ __('Unit Price') }} ({{ $cs }})</th>
                                            <th class="q2-right">{{ __('Disc %') }}</th>
                                            <th class="q2-right">{{ __('Tax %') }}</th>
                                            <th class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                        </tr></thead>
                                        <tbody>
                                            @forelse($invoice->lines as $line)
                                                <tr>
                                                    <td class="q2-amt q2-mono">{{ $line->product?->sku ?? '—' }}</td>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">
                                                        {{ $line->product?->name ?? '—' }}
                                                        @if ($line->costCenter?->name)
                                                            <span class="block text-[11px] font-normal" style="color:var(--muted,#5F7476)">{{ $line->costCenter->code }} - {{ $line->costCenter->name }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $line->description }}</td>
                                                    <td class="q2-right">{{ number_format((float) $line->quantity, 2) }}</td>
                                                    <td class="q2-right q2-amt">{{ format_number((float) $line->unit_price) }}</td>
                                                    <td class="q2-right">{{ $line->discount }}%</td>
                                                    <td class="q2-right">{{ $line->tax_rate }}%</td>
                                                    <td class="q2-right q2-amt" style="font-weight:800">{{ format_number((float) $line->line_total) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="q2-empty">{{ __('No line items on this invoice.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex justify-end mt-4 px-5 pb-5">
                                    <div class="q2-railsum" style="width:16rem">
                                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval">{{ format_number($invSubtotal) }}</span></div>
                                        <div class="q2-srow"><span>{{ __('Tax') }}</span><span class="q2-sval">{{ format_number($invTax) }}</span></div>
                                        <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval">{{ format_number($invTotal) }}</span></div>
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
                        <a href="{{ route('accounting.invoices.print', $invoice) }}" target="_blank" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Print / PDF') }}
                        </a>
                        @if($invoice->status === 'draft')
                            <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="q2-vitem">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Edit Invoice') }}
                            </a>
                        @endif
                        @if($copyQuotes->isNotEmpty())
                            <button type="button" class="q2-vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer" onclick="CopyQuote.open()">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Copy from Quote') }}
                            </button>
                        @endif
                        @if($invoice->customer)
                            <a href="{{ route('accounting.customers.show', $invoice->customer) }}" class="q2-vitem">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                {{ __('View Customer') }}
                            </a>
                        @endif
                        <a href="{{ route('accounting.invoices.create') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('New Invoice') }}
                        </a>
                        <a href="{{ route('accounting.invoices.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('All Invoices') }}
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <x-copy-quote-picker :quotes="$copyQuotes" mode="navigate" />

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
