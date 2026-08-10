<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $invSubtotal = $invoice->lines->sum(fn ($l) => (float) $l->amount);
        $invTax = $invoice->lines->sum(fn ($l) => (float) $l->tax_amount);
        $invTotal = $invSubtotal + $invTax;
        $invPaid = (float) $invoice->amount_paid;
        $invBalance = max($invTotal - $invPaid, 0);
        $statusMap = [
            'draft' => ['draft', __('Draft')],
            'sent' => ['teal', __('Sent')],
            'partially_paid' => ['mint', __('Partially Paid')],
            'paid' => ['act', __('Paid')],
            'overdue' => ['red', __('Overdue')],
            'void' => ['void', __('Void')],
        ];
        [$statusCls, $statusLabel] = $statusMap[$invoice->status] ?? ['gray', ucfirst(str_replace('_', ' ', $invoice->status))];
        $methodLabel = fn ($m) => ucfirst(str_replace('_', ' ', $m));
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Invoice') }} <span class="mono-chip">{{ $invoice->invoice_number }}</span></h1>
                    <div class="sub">{{ $invoice->customer->name ?? __('—') }} · {{ __('issued') }} {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}
                        @if ($invoice->due_date) · {{ __('due') }} {{ $invoice->due_date->format('M d, Y') }} @endif
                    </div>
                </div>
                <div class="tbtns">
                    @if($invoice->status === 'draft')
                        <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="btn btn-sec">{{ __('Edit') }}</a>
                        @can('invoices.post')
                            <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline" onsubmit="return fbConfirmButton(event, 'Post this invoice?', { type: 'action' })">
                                @csrf
                                <button type="submit" class="btn btn-cta">{{ __('Post Invoice') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                        @can('invoices.void')
                            <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Are you sure you want to void this invoice?') }}', { type: 'danger' })">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-danger">{{ __('Void Invoice') }}</button>
                            </form>
                        @endcan
                    @endif
                    <a href="{{ route('accounting.invoices.print', $invoice) }}" target="_blank" class="btn btn-ghost">{{ __('Print') }}</a>
                    <a href="{{ route('accounting.invoices.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
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
                                <div class="n">{{ __('Invoice') }} {{ $invoice->invoice_number }} <span class="badge b-{{ $statusCls }}"><span class="bdot"></span>{{ $statusLabel }}</span></div>
                                <div class="c">
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $invoice->customer->name ?? '—' }}</span>
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ __('Date') }} · {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</span>
                                    @if ($invoice->due_date)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ __('Due') }} · {{ $invoice->due_date->format('M d, Y') }}</span>
                                    @endif
                                    @if($invoice->reference)
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>{{ $invoice->reference }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="acts">
                                <a href="{{ route('accounting.invoices.print', $invoice) }}" target="_blank" class="btn btn-ghost btn-sm">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ __('Print / PDF') }}
                                </a>
                                @if($invoice->status === 'draft')
                                    <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="btn btn-sec btn-sm">{{ __('Edit') }}</a>
                                @endif
                            </div>
                        </div>
                    </section>

                    {{-- stat grid --}}
                    <div class="statgrid">
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v10M15 9.5c-.6-1-1.7-1.5-3-1.5-1.8 0-3 .9-3 2.2 0 2.8 6 1.6 6 4.3 0 1.3-1.2 2.2-3 2.2-1.3 0-2.4-.5-3-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Total') }} ({{ $cs }})</div><div class="v">{{ format_number($invTotal) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div><div class="l">{{ __('Paid') }} ({{ $cs }})</div><div class="v mint">{{ format_number($invPaid) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <div><div class="l">{{ __('Balance Due') }} ({{ $cs }})</div><div class="v @if($invBalance > 0 && $invoice->status === 'overdue') red @endif">{{ format_number($invBalance) }}</div></div></div>
                        <div class="sbox ic"><span class="t"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <div><div class="l">{{ __('Tax') }} ({{ $cs }})</div><div class="v">{{ format_number($invTax) }}</div></div></div>
                    </div>

                    {{-- tabs --}}
                    <section class="card">
                        <div class="card-sec" style="padding-bottom:8px">
                            <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Contents') }}</h2><span class="rule"></span></div>
                            <div class="tabs" role="tablist">
                                <button type="button" class="tab on" data-target="tab-overview" role="tab">{{ __('Overview') }}</button>
                                <button type="button" class="tab" data-target="tab-payments" role="tab">{{ __('Payments') }}</button>
                                <button type="button" class="tab" data-target="tab-lines" role="tab">{{ __('Line Items') }}</button>
                            </div>

                            <div class="tab-panel" id="tab-overview">
                                <div class="g4">
                                    <div class="field"><label>{{ __('Customer') }}</label><a href="{{ route('accounting.customers.show', $invoice->customer) }}" style="font-weight:600;color:var(--sec,#128F8E)">{{ $invoice->customer->name ?? '—' }}</a></div>
                                    <div class="field"><label>{{ __('Invoice Date') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Due Date') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Reference') }}</label><span class="mono">{{ $invoice->reference ?? '—' }}</span></div>
                                    <div class="field"><label>{{ __('Created By') }}</label><span style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $invoice->createdBy?->name ?? '—' }}</span></div>
                                    @if ($invoice->journalEntry)
                                        <div class="field"><label>{{ __('Journal Entry') }}</label><a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $invoice->journalEntry->reference ?? ('JE-' . str_pad($invoice->journalEntry->id, 4, '0', STR_PAD_LEFT)) }}</a></div>
                                    @endif
                                    @if ($invoice->memo)
                                        <div class="field sp2"><label>{{ __('Description') }}</label><span class="em" style="font-size:.8125rem">{{ $invoice->memo }}</span></div>
                                    @endif
                                </div>
                            </div>

                            <div class="tab-panel" id="tab-payments" style="display:none">
                                <div class="li-wrap">
                                    <table>
                                        <thead><tr>
                                            <th style="width:22%">{{ __('Date') }}</th>
                                            <th style="width:30%">{{ __('Reference') }}</th>
                                            <th class="num" style="width:24%">{{ __('Amount') }} ({{ $cs }})</th>
                                            <th style="width:24%">{{ __('Method') }}</th>
                                        </tr></thead>
                                        <tbody>
                                            @forelse($payments as $payment)
                                                <tr>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                                    <td class="em mono">{{ $payment->reference ?? '—' }}</td>
                                                    <td class="numr">{{ format_number($payment->pivot->amount) }}</td>
                                                    <td class="em">{{ $methodLabel($payment->payment_method) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="em" style="padding:22px;text-align:center">{{ __('No payments recorded yet.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($payments->isNotEmpty())
                                    <div class="li-totals" style="margin-top:12px"><div class="box">
                                        <div class="trow"><span>{{ __('Paid') }}</span><span class="v">{{ format_number($invPaid) }}</span></div>
                                        <div class="trow"><span>{{ __('Balance Due') }}</span><span class="v">{{ format_number($invBalance) }}</span></div>
                                        <div class="trow total"><span>{{ __('Total') }}</span><span class="v">{{ format_number($invTotal) }}</span></div>
                                    </div></div>
                                @endif
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
                                            @forelse($invoice->lines as $line)
                                                <tr>
                                                    <td class="mono em">{{ $line->product?->sku ?? '—' }}</td>
                                                    <td style="font-weight:600;color:var(--ink,#0B2A2D)">
                                                        {{ $line->product?->name ?? '—' }}
                                                        @if ($line->costCenter?->name)
                                                            <div style="font-size:11px;font-weight:400;color:var(--muted,#5F7476)">{{ $line->costCenter->code }} - {{ $line->costCenter->name }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="em">{{ $line->description }}</td>
                                                    <td class="numr">{{ number_format((float) $line->quantity, 2) }}</td>
                                                    <td class="numr">{{ format_number((float) $line->unit_price) }}</td>
                                                    <td class="numr">{{ $line->discount }}%</td>
                                                    <td class="numr">{{ $line->tax_rate }}%</td>
                                                    <td class="numr" style="font-weight:800">{{ format_number((float) $line->line_total) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="em" style="padding:22px;text-align:center">{{ __('No line items on this invoice.') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="li-totals" style="margin-top:12px"><div class="box">
                                    <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v">{{ format_number($invSubtotal) }}</span></div>
                                    <div class="trow"><span>{{ __('Tax') }}</span><span class="v">{{ format_number($invTax) }}</span></div>
                                    <div class="trow total"><span>{{ __('Total') }}</span><span class="v">{{ format_number($invTotal) }}</span></div>
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
                                <div class="srow"><span class="l">{{ __('Subtotal') }}</span><span class="v">{{ format_number($invSubtotal) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Tax') }}</span><span class="v">{{ format_number($invTax) }}</span></div>
                                <div class="srow strong"><span class="l">{{ __('Total') }}</span><span class="v">{{ format_number($invTotal) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Paid') }}</span><span class="v mint">{{ format_number($invPaid) }}</span></div>
                                <div class="srow"><span class="l">{{ __('Balance Due') }}</span><span class="v">{{ format_number($invBalance) }}</span></div>
                            </div>
                            <div class="gt"><span class="l">{{ __('Balance Due') }}</span><span class="v">{{ $cs }}{{ format_number($invBalance) }}</span></div>
                        </div>
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                <a href="{{ route('accounting.invoices.print', $invoice) }}" target="_blank" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Print / PDF') }}</a>
                                @if($invoice->status === 'draft')
                                    <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Edit Invoice') }}</a>
                                @endif
                                @if($copyQuotes->isNotEmpty())
                                    <button type="button" class="vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer" onclick="CopyQuote.open()"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Copy from Quote') }}</button>
                                @endif
                                @if($invoice->customer)
                                    <a href="{{ route('accounting.customers.show', $invoice->customer) }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('View Customer') }}</a>
                                @endif
                                <a href="{{ route('accounting.invoices.create') }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('New Invoice') }}</a>
                                <a href="{{ route('accounting.invoices.index') }}" class="vitem"><span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('All Invoices') }}</a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>

    <x-copy-quote-picker :quotes="$copyQuotes" mode="navigate" />

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
