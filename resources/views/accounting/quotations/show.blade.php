<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $isDecisionState = in_array($quotation->status, ['sent'], true);
        $validDays = $quotation->valid_until
            ? (int) now()->startOfDay()->diffInDays($quotation->valid_until->copy()->startOfDay(), false)
            : null;
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §4 sticky page head --}}
            <div class="q2-head q2-head--sticky">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="q2-title">{{ __('Quotation') }} <span class="q2-mono">{{ $quotation->quotation_number }}</span></h1>
                        <span class="q2-badge q2-badge--{{ $quotation->status }}">
                            @switch($quotation->status)
                                @case('draft') <span class="q2-dot"></span>{{ __('Draft') }} @break
                                @case('sent') <span class="q2-dot"></span>{{ __('Sent') }} @break
                                @case('accepted') <span class="q2-dot"></span>{{ __('Accepted') }} @break
                                @case('declined') <span class="q2-dot"></span>{{ __('Declined') }} @break
                                @case('converted') <span class="q2-dot"></span>{{ __('Converted') }} @break
                                @case('void') <span class="q2-dot"></span>{{ __('Void') }} @break
                            @endswitch
                        </span>
                    </div>
                    <p class="q2-sub">{{ $quotation->customer->name ?? __('No customer') }} · {{ __('quoted') }} {{ $quotation->quotation_date?->format('M d, Y') ?? '—' }} · {{ __('valid until') }} {{ $quotation->valid_until?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div class="q2-head-actions">
                    @if($quotation->status === 'draft')
                        <a href="{{ route('accounting.quotations.edit', $quotation) }}" class="q2-btn q2-btn--sec">{{ __('Edit') }}</a>
                        @if($quotation->customer && $quotation->customer->email)
                            <form method="POST" action="{{ route('accounting.quotations.email', $quotation) }}" class="inline">
                                @csrf
                                <button type="submit" class="q2-btn q2-btn--ghost">{{ __('Email to Customer') }}</button>
                            </form>
                        @endif
                        @can('quotations.send')
                            <form method="POST" action="{{ route('accounting.quotations.send', $quotation) }}" class="inline">@csrf<button type="submit" class="q2-btn q2-btn--cta">{{ __('Mark as Sent') }}</button></form>
                        @endcan
                    @endif
                    @if(in_array($quotation->status, ['sent', 'accepted']))
                        @can('quotations.convert')
                            <form method="POST" action="{{ route('accounting.quotations.convert-to-invoice', $quotation) }}" class="inline">@csrf<button type="submit" class="q2-btn q2-btn--cta">{{ __('Convert to Invoice') }}</button></form>
                        @endcan
                    @endif
                    @if(in_array($quotation->status, ['draft', 'sent', 'accepted']))
                        @can('quotations.void')
                            <form method="POST" action="{{ route('accounting.quotations.void', $quotation) }}" class="inline" onsubmit="return fbPromptForm(event, '{{ __('Enter void reason') }}:')">
                                @csrf<input type="hidden" name="void_reason" value="" />
                                <button type="submit" class="q2-btn q2-btn--danger">{{ __('Void') }}</button>
                            </form>
                        @endcan
                    @endif
                    <a href="{{ route('accounting.quotations.print', $quotation) }}" target="_blank" class="q2-btn q2-btn--ghost">{{ __('Print') }}</a>
                    <a href="{{ route('accounting.quotations.index') }}" class="q2-btn q2-btn--ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">

                    {{-- §4 profile header --}}
                    <div class="q2-prof">
                        <div class="q2-pbar">
                            <div class="q2-pid">
                                <span class="q2-pic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <div>
                                    <div class="q2-plabel">{{ __('Quotation') }} №</div>
                                    <div class="q2-pname">{{ $quotation->quotation_number }}</div>
                                    <div class="q2-pmeta">
                                        <span class="q2-cchip"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $quotation->customer->name ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Date') }} · {{ $quotation->quotation_date?->format('M d, Y') ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Valid Until') }} · {{ $quotation->valid_until?->format('M d, Y') ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Currency') }} · {{ $quotation->currency ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="q2-pacts">
                                <a href="{{ route('accounting.quotations.print', $quotation) }}" target="_blank" class="q2-btn q2-btn--ghost q2-btn--sm">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ __('Print / PDF') }}
                                </a>
                                @if($quotation->status === 'draft')
                                    <a href="{{ route('accounting.quotations.edit', $quotation) }}" class="q2-btn q2-btn--soft q2-btn--sm">{{ __('Edit') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- §4 tabs --}}
                    <div class="q2-tabs" role="tablist">
                        <button type="button" class="q2-tab is-active" data-target="tab-overview" role="tab">{{ __('Overview') }}</button>
                        <button type="button" class="q2-tab" data-target="tab-lines" role="tab">{{ __('Line Items') }}</button>
                        <button type="button" class="q2-tab" data-target="tab-files" role="tab">{{ __('Attachments') }}</button>
                    </div>

                    <div class="q2-tdiv">
                        {{-- overview tab --}}
                        <section id="tab-overview" class="q2-tab-panel">
                            <div class="q2-statgrid">
                                <div class="q2-stat">
                                    <span class="q2-stat-ic q2-stat-ic--teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <div class="q2-stat-meta">
                                        <span class="q2-stat-lbl">{{ __('Subtotal') }}</span>
                                        <span class="q2-stat-val">{{ format_number($quotation->amount) }}</span>
                                        <span class="q2-stat-var">{{ $cs }}</span>
                                    </div>
                                </div>
                                <div class="q2-stat">
                                    <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <div class="q2-stat-meta">
                                        <span class="q2-stat-lbl">{{ __('Tax') }}</span>
                                        <span class="q2-stat-val">{{ format_number($quotation->tax_total) }}</span>
                                        <span class="q2-stat-var">{{ $cs }}</span>
                                    </div>
                                </div>
                                <div class="q2-stat">
                                    <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12V8a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <div class="q2-stat-meta">
                                        <span class="q2-stat-lbl">{{ __('Grand Total') }}</span>
                                        <span class="q2-stat-val">{{ format_number($quotation->total) }}</span>
                                        <span class="q2-stat-var">{{ $cs }}</span>
                                    </div>
                                </div>
                                <div class="q2-stat">
                                    <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <div class="q2-stat-meta">
                                        <span class="q2-stat-lbl">{{ __('Validity') }}</span>
                                        <span class="q2-stat-val">{{ $validDays !== null && $validDays >= 0 ? $validDays . ' ' . __('days') : __('Expired') }}</span>
                                        <span class="q2-stat-var">{{ $quotation->valid_until?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- decision / outcome --}}
                            @if($isDecisionState)
                                <div class="q2-sec mt-4">
                                    <div class="q2-sec-head">
                                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                        <h2 class="q2-sec-title">{{ __('Review & Decide') }}</h2>
                                    </div>
                                    <p class="q2-hint mt-4">{{ __('Accept to proceed with this quotation, or decline it.') }}</p>
                                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3.5 border-t pt-4" style="border-color:var(--line,#E2ECEC)">
                                        <form id="quotation-decline-form" method="POST" action="{{ route('accounting.quotations.decline', $quotation) }}" class="inline">@csrf</form>
                                        <form id="quotation-accept-form" method="POST" action="{{ route('accounting.quotations.accept', $quotation) }}" class="inline">@csrf</form>
                                        <x-review.btn variant="reject" type="submit" form="quotation-decline-form">{{ __('Decline') }}</x-review.btn>
                                        <x-review.btn variant="primary" size="lg" type="submit" form="quotation-accept-form">{{ __('Accept') }}</x-review.btn>
                                    </div>
                                </div>
                            @elseif(in_array($quotation->status, ['accepted', 'declined', 'converted'], true))
                                @php
                                    $quotationOutcome = match ($quotation->status) {
                                        'accepted' => ['chip' => 'ACCEPTED', 'tone' => 'approved', 'title' => __('Quotation accepted')],
                                        'converted' => ['chip' => 'CONVERTED', 'tone' => 'approved', 'title' => __('Quotation converted to invoice')],
                                        default => ['chip' => 'DECLINED', 'tone' => 'rejected', 'title' => __('Quotation declined')],
                                    };
                                @endphp
                                <x-review.outcome
                                    :title="$quotationOutcome['title']"
                                    :description="__('This quotation is no longer open for decision.')"
                                    :chip="$quotationOutcome['chip']"
                                    :tone="$quotationOutcome['tone']"
                                />
                            @endif

                            <div class="q2-sec mt-4">
                                <div class="q2-sec-head">
                                    <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <h2 class="q2-sec-title">{{ __('Quotation Details') }}</h2>
                                </div>
                                <div class="q2-g4 mt-5">
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Quotation Number') }}</span>
                                        <span class="q2-amt q2-mono">{{ $quotation->quotation_number }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Customer') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $quotation->customer->name ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Date') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $quotation->quotation_date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Valid Until') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $quotation->valid_until?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Reference') }}</span>
                                        <span class="q2-amt q2-mono">{{ $quotation->reference ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Currency') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $quotation->currency ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Branch') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $quotation->branch->name ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Created By') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $quotation->createdByUser->name ?? '—' }}</span>
                                    </div>
                                    @if($quotation->memo)
                                        <div class="q2-field" style="grid-column: span 2">
                                            <span class="q2-label">{{ __('Description') }}</span>
                                            <p class="q2-rail-memo">{{ $quotation->memo }}</p>
                                        </div>
                                    @endif
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
                                            <th class="q2-right">{{ __('Tax') }} ({{ $cs }})</th>
                                            <th class="q2-right">{{ __('Total') }} ({{ $cs }})</th>
                                        </tr></thead>
                                        <tbody>
                                            @foreach($quotation->lines as $line)
                                                <tr>
                                                    <td>{{ $line->product->name ?? '—' }}</td>
                                                    <td>{{ $line->description }}</td>
                                                    <td class="q2-right">{{ number_format($line->quantity, 2) }}</td>
                                                    <td class="q2-right q2-amt">{{ format_number($line->unit_price) }}</td>
                                                    <td class="q2-right q2-amt">{{ format_number($line->tax_amount) }}</td>
                                                    <td class="q2-right q2-amt" style="font-weight:800">{{ format_number($line->line_total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex justify-end mt-4 px-5 pb-5">
                                    <div class="q2-railsum" style="width:16rem">
                                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval">{{ format_number($quotation->amount) }}</span></div>
                                        <div class="q2-srow"><span>{{ __('Tax') }}</span><span class="q2-sval">{{ format_number($quotation->tax_total) }}</span></div>
                                        <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval">{{ format_number($quotation->total) }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- attachments tab --}}
                        <section id="tab-files" class="q2-tab-panel">
                            @if($quotation->attachments->isNotEmpty())
                                <div class="q2-sec">
                                    <div class="q2-sec-head">
                                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                        <h2 class="q2-sec-title">{{ __('Attachments') }}</h2>
                                    </div>
                                    <ul class="q2-li-wrap">
                                        @foreach($quotation->attachments as $attachment)
                                            <li class="q2-li">
                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path) }}" target="_blank" class="q2-li-name q2-link">{{ $attachment->name }}</a>
                                                <span class="q2-li-size">{{ format_bytes($attachment->file_size) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <div class="q2-card">
                                    <p class="q2-empty">{{ __('No attachments for this quotation.') }}</p>
                                </div>
                            @endif
                        </section>
                    </div>
                </div>

                {{-- §4 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Actions') }}</div>
                        <a href="{{ route('accounting.quotations.print', $quotation) }}" target="_blank" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Print / PDF') }}
                        </a>
                        @if($quotation->status === 'draft')
                            <a href="{{ route('accounting.quotations.edit', $quotation) }}" class="q2-vitem">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Edit Quotation') }}
                            </a>
                        @endif
                        <a href="{{ route('accounting.quotations.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Back to Quotations') }}
                        </a>
                        <div class="q2-rule"></div>
                        <a href="{{ route('accounting.reports.quotation-status') }}" class="q2-vitem q2-link">{{ __('Quotation Status Report') }}</a>
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
