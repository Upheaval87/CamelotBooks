<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $invSubtotal = $invoice->lines->sum(fn ($l) => (float) $l->amount);
        $invTax = $invoice->lines->sum(fn ($l) => (float) $l->tax_amount);
        $invTotal = $invSubtotal + $invTax;
        $invPaid = (float) $invoice->amount_paid;
        $invBalance = max($invTotal - $invPaid, 0);
        $invStatusMap = [
            'draft' => ['label' => 'Draft', 'class' => 'x-badge--gray'],
            'sent' => ['label' => 'Sent', 'class' => 'x-badge--teal'],
            'paid' => ['label' => 'Paid', 'class' => 'x-badge--mint'],
            'overdue' => ['label' => 'Overdue', 'class' => 'x-badge--red'],
            'void' => ['label' => 'Void', 'class' => 'x-badge--gray'],
        ];
        $invStatus = $invStatusMap[$invoice->status] ?? ['label' => ucfirst($invoice->status), 'class' => 'x-badge--gray'];
    @endphp

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- page head --}}
            <div class="flex items-start justify-between gap-4 flex-wrap pb-4 mb-6 border-b border-line">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">
                        {{ __('Invoice') }} #{{ $invoice->invoice_number }}
                        <span class="x-badge {{ $invStatus['class'] }} x-head-badge">
                            <span class="x-badge-dot"></span>{{ __($invStatus['label']) }}
                        </span>
                    </h1>
                    <p class="x-page-sub">
                        Issued {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}
                        @if ($invoice->due_date)
                            · Due {{ $invoice->due_date->format('M d, Y') }}
                        @endif
                    </p>
                </div>
                <div class="x-tb">
                    <div class="x-tb-group">
                        <span class="x-tb-label">{{ __('Create') }}</span>
                        <a href="{{ route('accounting.invoices.create') }}" class="x-tb-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v16m8-8H4"/></svg>
                            {{ __('New') }}
                        </a>
                        @if ($invoice->status === 'draft')
                            @can('invoices.post')
                                <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="x-tb-btn x-tb-btn--cta">{{ __('Save') }}</button>
                                </form>
                            @endcan
                            <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="x-tb-btn x-tb-btn--cta">{{ __('Save & Send') }}</a>
                        @endif
                    </div>
                    <span class="x-tb-divider"></span>
                    <div class="x-tb-group">
                        <span class="x-tb-label">{{ __('Reference') }}</span>
                        <a href="{{ route('accounting.customers.show', $invoice->customer) }}" class="x-tb-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            {{ __('Customer') }}
                        </a>
                        @if ($copyQuotes->isNotEmpty())
                            <button type="button" class="x-tb-btn" onclick="CopyQuote.open()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z"/></svg>
                                {{ __('Copy from Quote') }}
                            </button>
                        @endif
                    </div>
                    <span class="x-tb-divider"></span>
                    <div class="x-tb-group">
                        <span class="x-tb-label">{{ __('Document') }}</span>
                        <button type="button" class="x-tb-btn" onclick="window.print()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
                            {{ __('Print') }}
                        </button>
                        @if ($invoice->customer && $invoice->customer->email)
                            <a href="mailto:{{ $invoice->customer->email }}?subject=Invoice {{ $invoice->invoice_number }}" class="x-tb-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                                {{ __('Email Invoice') }}
                            </a>
                        @endif
                        @if ($invoice->journalEntry)
                            <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="x-tb-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ __('Journal') }}
                            </a>
                        @endif
                    </div>
                    <span class="x-tb-spacer"></span>
                    @if (in_array($invoice->status, ['sent', 'paid', 'overdue']))
                        @can('invoices.void')
                            <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="x-tb-btn x-tb-btn--danger" onclick="return fbConfirmButton(event, '{{ __('Are you sure you want to void this invoice?') }}', { type: 'danger' })">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                    {{ __('Void') }}
                                </button>
                            </form>
                        @endcan
                    @endif
                    <a href="{{ route('accounting.invoices.index') }}" class="x-tb-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('All Invoices') }}
                    </a>
                </div>
            </div>

            <x-input-error :messages="$errors->get('error')" class="mb-4" />

            <div class="grid gap-6 items-start lg:grid-cols-[1fr_340px]">
                <div class="flex flex-col gap-5 min-w-0">

                    {{-- info card --}}
                    <section class="card rounded-[20px] p-6 xl:p-[26px]">
                        <div class="x-sec">
                            <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6"/></svg></span>
                            <h2 class="x-sec-h2">{{ __('Invoice Information') }}</h2>
                            <span class="x-sec-rule"></span>
                        </div>
                        <div class="detail-grid">
                            <x-detail-field :label="__('Customer')">
                                <a href="{{ route('accounting.customers.show', $invoice->customer) }}" class="font-semibold text-[#128F8E] hover:text-[#0C6B6A]">{{ $invoice->customer->name ?? '—' }}</a>
                            </x-detail-field>
                            <x-detail-field :label="__('Invoice Date')" :value="$invoice->invoice_date?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Due Date')" :value="$invoice->due_date?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Reference')" :value="$invoice->reference ?? '—'" />
                            <x-detail-field :label="__('Created By')" :value="$invoice->createdBy?->name ?? '—'" />
                            @if ($invoice->memo)
                                <x-detail-field :label="__('Description')" class="col-span-3">{{ $invoice->memo }}</x-detail-field>
                            @endif
                        </div>
                    </section>

                    {{-- line items --}}
                    <section class="card rounded-[20px] p-6 xl:p-[26px]">
                        <div class="x-sec">
                            <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                            <h2 class="x-sec-h2">{{ __('Line Items') }}</h2>
                            <span class="x-sec-rule"></span>
                            <span class="x-chip">{{ $invoice->lines->count() }} {{ __('lines') }}</span>
                        </div>

                        <div class="mt-4 border border-shell rounded-[14px] overflow-visible round-thead-clip bg-[#fbfcfe]">
                            <table class="x-wset-view w-full border-collapse text-[13px] table-fixed">
                                <thead>
                                    <tr>
                                        <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Item Code') }}</th>
                                        <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Item') }}</th>
                                        <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Description') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Qty') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Unit Price') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Disc %') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Tax %') }}</th>
                                        <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($invoice->lines as $line)
                                        <tr>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-[12px] text-gray-500">{{ $line->product?->sku ?? '—' }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle font-semibold text-gray-900">
                                                {{ $line->product?->name ?? '—' }}
                                                @if ($line->costCenter?->name)
                                                    <span class="block text-[11px] font-normal text-slate-400">{{ $line->costCenter->code }} - {{ $line->costCenter->name }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-gray-600">{{ $line->description }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right tabular-nums text-gray-900">{{ $line->quantity }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right tabular-nums text-gray-900">{{ number_format((float) $line->unit_price, 2) }}</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right tabular-nums text-gray-600">{{ $line->discount }}%</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right tabular-nums text-gray-600">{{ $line->tax_rate }}%</td>
                                            <td class="py-3 px-2.5 border-b border-line align-middle text-right font-bold tabular-nums text-gray-900">{{ number_format((float) $line->line_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="py-6 text-center text-sm text-slate-400">{{ __('No line items on this invoice.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    @if ($invoice->journalEntry)
                        <section class="card rounded-[20px] p-6 xl:p-[26px]">
                            <div class="x-sec">
                                <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 12h6M9 16h6M8 7h8"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg></span>
                                <h2 class="x-sec-h2">{{ __('Journal Entry') }}</h2>
                                <span class="x-sec-rule"></span>
                            </div>
                            <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="text-sm font-semibold text-[#128F8E] hover:text-[#0C6B6A]">
                                {{ $invoice->journalEntry->reference }} — {{ __('View Journal Entry') }}
                            </a>
                        </section>
                    @endif

                    @if ($invoice->payments->count() > 0)
                        <section class="card rounded-[20px] p-6 xl:p-[26px]">
                            <div class="x-sec">
                                <span class="x-sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg></span>
                                <h2 class="x-sec-h2">{{ __('Payment History') }}</h2>
                                <span class="x-sec-rule"></span>
                            </div>
                            <div class="mt-4 overflow-x-auto">
                                <table class="datasheet w-full text-[13px]">
                                    <thead>
                                        <tr>
                                            <th class="text-left">{{ __('Date') }}</th>
                                            <th class="text-left">{{ __('Reference') }}</th>
                                            <th class="text-right">{{ __('Amount') }}</th>
                                            <th class="text-left">{{ __('Method') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoice->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                                <td>{{ $payment->reference ?? '—' }}</td>
                                                <td class="numeric">{{ number_format((float) $payment->pivot->amount, 2) }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endif
                </div>

                {{-- right rail --}}
                <aside class="x-rail-wrap">
                    <div class="x-rail">
                        <div class="x-rail-card">
                            <div class="x-rail-title">{{ __('Summary') }}</div>
                            <div class="x-rail-v"><span class="x-rail-vl">{{ __('Subtotal') }}</span><span class="x-rail-vv">{{ number_format($invSubtotal, 2) }}</span></div>
                            <div class="x-rail-v"><span class="x-rail-vl">{{ __('Tax') }}</span><span class="x-rail-vv">{{ number_format($invTax, 2) }}</span></div>
                            <div class="x-rail-gt"><span class="x-rail-vl">{{ __('Total') }}</span><span class="x-rail-vv">{{ $cs }}{{ number_format($invTotal, 2) }}</span></div>
                            <div class="x-rail-v"><span class="x-rail-vl">{{ __('Paid') }}</span><span class="x-rail-vv">{{ number_format($invPaid, 2) }}</span></div>
                            <div class="x-rail-v"><span class="x-rail-vl">{{ __('Balance Due') }}</span><span class="x-rail-vv {{ $invBalance > 0 ? '' : '' }}">{{ number_format($invBalance, 2) }}</span></div>
                        </div>

                        <nav class="x-rail-card">
                            <div class="x-rail-title">{{ __('Quick Links') }}</div>
                            <div class="x-rail-nav">
                                @if ($copyQuotes->isNotEmpty())
                                    <button type="button" class="x-rail-link" onclick="CopyQuote.open()">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z"/></svg>
                                        {{ __('Copy from Quote') }}
                                    </button>
                                @endif
                                <a href="{{ route('accounting.invoices.print', $invoice) }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/></svg>
                                    {{ __('Printable Invoice') }}
                                </a>
                                <a href="{{ route('accounting.customers.show', $invoice->customer) }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg>
                                    {{ __('View Customer') }}
                                </a>
                                <a href="{{ route('accounting.invoices.create') }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v16m8-8H4"/></svg>
                                    {{ __('New Invoice') }}
                                </a>
                                <a href="{{ route('accounting.invoices.index') }}" class="x-rail-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    {{ __('All Invoices') }}
                                </a>
                            </div>
                        </nav>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <x-copy-quote-picker :quotes="$copyQuotes" mode="navigate" />
</x-app-layout>
