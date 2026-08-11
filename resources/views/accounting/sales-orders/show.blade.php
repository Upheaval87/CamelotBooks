<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $deliveryDays = $order->expected_delivery_date
            ? (int) now()->startOfDay()->diffInDays($order->expected_delivery_date->copy()->startOfDay(), false)
            : null;
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- §4 sticky page head --}}
            <div class="q2-head q2-head--sticky">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="q2-title" style="font-size:1.375rem">{{ __('Sales Order') }} <span class="q2-mono">{{ $order->sales_order_number }}</span></h1>
                        <span class="q2-badge q2-badge--{{ $order->status }}">
                            @switch($order->status)
                                @case('draft') <span class="q2-dot"></span>{{ __('Draft') }} @break
                                @case('sent') <span class="q2-dot"></span>{{ __('Sent') }} @break
                                @case('confirmed') <span class="q2-dot"></span>{{ __('Confirmed') }} @break
                                @case('fulfilled') <span class="q2-dot"></span>{{ __('Fulfilled') }} @break
                                @case('cancelled') <span class="q2-dot"></span>{{ __('Cancelled') }} @break
                                @case('void') <span class="q2-dot"></span>{{ __('Void') }} @break
                            @endswitch
                        </span>
                    </div>
                    <p class="q2-sub">{{ $order->customer->name ?? __('No customer') }} · {{ __('ordered') }} {{ $order->order_date?->format('M d, Y') ?? '—' }} · {{ __('delivery') }} {{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div class="q2-head-actions">
                    @if($order->status === 'draft')
                        @can('sales-orders.edit')
                            <a href="{{ route('accounting.sales-orders.edit', $order) }}" class="q2-btn q2-btn--sec">{{ __('Edit') }}</a>
                        @endcan
                        @can('sales-orders.send')
                            <form method="POST" action="{{ route('accounting.sales-orders.send', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Mark this sales order as sent?') }}', { type: 'action' })">@csrf<button type="submit" class="q2-btn q2-btn--cta">{{ __('Send') }}</button></form>
                        @endcan
                        @can('sales-orders.cancel')
                            <form method="POST" action="{{ route('accounting.sales-orders.cancel', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Cancel this draft sales order?') }}', { type: 'danger' })">@csrf<button type="submit" class="q2-btn q2-btn--danger">{{ __('Cancel') }}</button></form>
                        @endcan
                    @endif

                    @if($order->status === 'sent')
                        @can('sales-orders.confirm')
                            <form method="POST" action="{{ route('accounting.sales-orders.confirm', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Confirm this sales order?') }}', { type: 'action' })">@csrf<button type="submit" class="q2-btn q2-btn--cta">{{ __('Confirm') }}</button></form>
                        @endcan
                    @endif

                    @if(in_array($order->status, ['sent', 'confirmed']))
                        @can('sales-orders.convert')
                            <form method="POST" action="{{ route('accounting.sales-orders.fulfill', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Mark this sales order as fulfilled?') }}', { type: 'action' })">@csrf<button type="submit" class="q2-btn q2-btn--sec">{{ __('Mark Fulfilled') }}</button></form>
                        @endcan
                    @endif

                    @if(in_array($order->status, ['sent', 'confirmed']) || ($order->status === 'fulfilled' && !$order->converted_invoice_id && !$order->converted_receipt_id))
                        @can('sales-orders.convert')
                            <form method="POST" action="{{ route('accounting.sales-orders.convert-to-invoice', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Create an invoice from this sales order?') }}', { type: 'action' })">@csrf<button type="submit" class="q2-btn q2-btn--cta">{{ __('Convert to Invoice') }}</button></form>
                        @endcan
                    @endif

                    @if(in_array($order->status, ['sent', 'confirmed']) || ($order->status === 'fulfilled' && !$order->converted_receipt_id && !$order->converted_invoice_id))
                        @can('sales-orders.convert')
                            <button type="button" class="q2-btn q2-btn--sec" x-data x-on:click="$dispatch('open-modal', 'convert-to-receipt')">{{ __('Convert to Receipt') }}</button>
                        @endcan
                    @endif

                    @if(in_array($order->status, ['draft', 'sent', 'confirmed']))
                        @can('sales-orders.cancel')
                            <form method="POST" action="{{ route('accounting.sales-orders.cancel', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Cancel this sales order?') }}', { type: 'danger' })">@csrf<button type="submit" class="q2-btn q2-btn--danger">{{ __('Cancel') }}</button></form>
                        @endcan
                        @can('sales-orders.void')
                            <form method="POST" action="{{ route('accounting.sales-orders.void', $order) }}" class="inline" onsubmit="return fbPromptForm(event, '{{ __('Enter void reason') }}:')">
                                @csrf<input type="hidden" name="void_reason" value="" />
                                <button type="submit" class="q2-btn q2-btn--danger">{{ __('Void') }}</button>
                            </form>
                        @endcan
                    @endif

                    <a href="{{ route('accounting.sales-orders.print', $order) }}" target="_blank" class="q2-btn q2-btn--ghost">{{ __('Print') }}</a>
                    <a href="{{ route('accounting.sales-orders.index') }}" class="q2-btn q2-btn--ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="q2-shell">
                <div class="q2-main">

                    {{-- §4 profile header --}}
                    <div class="q2-prof">
                        <div class="q2-pbar">
                            <div class="q2-pid">
                                <span class="q2-pic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <div>
                                    <div class="q2-plabel">{{ __('Sales Order') }} №</div>
                                    <div class="q2-pname">{{ $order->sales_order_number }}</div>
                                    <div class="q2-pmeta">
                                        <span class="q2-cchip"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $order->customer->name ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Order Date') }} · {{ $order->order_date?->format('M d, Y') ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Expected Delivery') }} · {{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</span>
                                        <span class="q2-cchip">{{ __('Currency') }} · {{ $order->currency ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="q2-pacts">
                                <a href="{{ route('accounting.sales-orders.print', $order) }}" target="_blank" class="q2-btn q2-btn--ghost q2-btn--sm">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ __('Print / PDF') }}
                                </a>
                                @if($order->status === 'draft' && auth()->user()->can('sales-orders.edit'))
                                    <a href="{{ route('accounting.sales-orders.edit', $order) }}" class="q2-btn q2-btn--soft q2-btn--sm">{{ __('Edit') }}</a>
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
                                        <span class="q2-stat-val">{{ format_number($order->amount) }}</span>
                                        <span class="q2-stat-var">{{ $cs }}</span>
                                    </div>
                                </div>
                                <div class="q2-stat">
                                    <span class="q2-stat-ic q2-stat-ic--mint"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <div class="q2-stat-meta">
                                        <span class="q2-stat-lbl">{{ __('Tax') }}</span>
                                        <span class="q2-stat-val">{{ format_number($order->tax_total) }}</span>
                                        <span class="q2-stat-var">{{ $cs }}</span>
                                    </div>
                                </div>
                                <div class="q2-stat">
                                    <span class="q2-stat-ic q2-stat-ic--ink"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12V8a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <div class="q2-stat-meta">
                                        <span class="q2-stat-lbl">{{ __('Grand Total') }}</span>
                                        <span class="q2-stat-val">{{ format_number($order->total) }}</span>
                                        <span class="q2-stat-var">{{ $cs }}</span>
                                    </div>
                                </div>
                                <div class="q2-stat">
                                    <span class="q2-stat-ic q2-stat-ic--steel"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <div class="q2-stat-meta">
                                        <span class="q2-stat-lbl">{{ __('Delivery') }}</span>
                                        <span class="q2-stat-val">{{ $deliveryDays !== null && $deliveryDays >= 0 ? $deliveryDays . ' ' . __('days') : ($deliveryDays !== null ? __('Overdue') : '—') }}</span>
                                        <span class="q2-stat-var">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- decision / outcome --}}
                            @if($order->status === 'sent')
                                <div class="q2-sec mt-4">
                                    <div class="q2-sec-head">
                                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                        <h2 class="q2-sec-title">{{ __('Review & Confirm') }}</h2>
                                    </div>
                                    <p class="q2-hint mt-4">{{ __('Confirm this order to commit the sale, or cancel it.') }}</p>
                                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3.5 border-t pt-4" style="border-color:var(--line,#E2ECEC)">
                                        <form id="so-cancel-form" method="POST" action="{{ route('accounting.sales-orders.cancel', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Cancel this sales order?') }}', { type: 'danger' })">@csrf</form>
                                        <form id="so-confirm-form" method="POST" action="{{ route('accounting.sales-orders.confirm', $order) }}" class="inline" onsubmit="return fbConfirmButton(event, '{{ __('Confirm this sales order?') }}', { type: 'action' })">@csrf</form>
                                        <x-review.btn variant="reject" type="submit" form="so-cancel-form">{{ __('Cancel') }}</x-review.btn>
                                        <x-review.btn variant="primary" size="lg" type="submit" form="so-confirm-form">{{ __('Confirm') }}</x-review.btn>
                                    </div>
                                </div>
                            @elseif(in_array($order->status, ['confirmed', 'fulfilled', 'cancelled', 'void'], true))
                                @php
                                    $soOutcome = match ($order->status) {
                                        'confirmed' => ['chip' => 'CONFIRMED', 'tone' => 'approved', 'title' => __('Sales order confirmed')],
                                        'fulfilled' => ['chip' => 'FULFILLED', 'tone' => 'approved', 'title' => __('Sales order fulfilled')],
                                        'void' => ['chip' => 'VOIDED', 'tone' => 'rejected', 'title' => __('Sales order voided')],
                                        default => ['chip' => 'CANCELLED', 'tone' => 'rejected', 'title' => __('Sales order cancelled')],
                                    };
                                    $soOutcomeDesc = match ($order->status) {
                                        'void' => $order->void_reason ? __('Reason') . ': ' . $order->void_reason : __('This sales order is no longer active.'),
                                        default => __('This sales order is no longer open for action.'),
                                    };
                                @endphp
                                <x-review.outcome
                                    :title="$soOutcome['title']"
                                    :description="$soOutcomeDesc"
                                    :chip="$soOutcome['chip']"
                                    :tone="$soOutcome['tone']"
                                />
                            @endif

                            @if($order->status === 'fulfilled' && ($order->converted_invoice_id || $order->converted_receipt_id))
                                <div class="q2-sec mt-4">
                                    <div class="q2-sec-head">
                                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                        <h2 class="q2-sec-title">{{ __('Conversion') }}</h2>
                                    </div>
                                    <div class="q2-g2 mt-5">
                                        @if($order->converted_invoice_id)
                                            <div class="q2-field">
                                                <span class="q2-label">{{ __('Invoice') }}</span>
                                                <a href="{{ route('accounting.invoices.show', $order->convertedInvoice) }}" class="q2-link q2-amt q2-mono">{{ $order->convertedInvoice->invoice_number ?? '—' }}</a>
                                            </div>
                                        @endif
                                        @if($order->converted_receipt_id)
                                            <div class="q2-field">
                                                <span class="q2-label">{{ __('Sales Receipt') }}</span>
                                                <a href="{{ route('accounting.sales-receipts.show', $order->convertedReceipt) }}" class="q2-link q2-amt q2-mono">{{ $order->convertedReceipt->receipt_number ?? '—' }}</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="q2-sec mt-4">
                                <div class="q2-sec-head">
                                    <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <h2 class="q2-sec-title">{{ __('Sales Order Details') }}</h2>
                                </div>
                                <div class="q2-g4 mt-5">
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Sales Order Number') }}</span>
                                        <span class="q2-amt q2-mono">{{ $order->sales_order_number }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Customer') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $order->customer->name ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Order Date') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $order->order_date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Expected Delivery') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Reference') }}</span>
                                        <span class="q2-amt q2-mono">{{ $order->reference ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Currency') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $order->currency ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Branch') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $order->branch->name ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Cost Centre') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $order->costCenter->name ?? '—' }}</span>
                                    </div>
                                    <div class="q2-field">
                                        <span class="q2-label">{{ __('Created By') }}</span>
                                        <span class="q2-amt" style="font-weight:600">{{ $order->createdByUser->name ?? '—' }}</span>
                                    </div>
                                    @if($order->posted_by)
                                        <div class="q2-field">
                                            <span class="q2-label">{{ __('Posted By') }}</span>
                                            <span class="q2-amt" style="font-weight:600">{{ $order->postedByUser->name ?? '—' }} · {{ $order->posted_at?->format('M d, Y') ?? '—' }}</span>
                                        </div>
                                    @endif
                                    @if($order->void_reason)
                                        <div class="q2-field" style="grid-column: span 2">
                                            <span class="q2-label">{{ __('Void Reason') }}</span>
                                            <p class="q2-rail-memo" style="font-size:.8125rem;color:var(--muted,#5F7476)">{{ $order->void_reason }}</p>
                                        </div>
                                    @endif
                                    @if($order->memo)
                                        <div class="q2-field" style="grid-column: span 2">
                                            <span class="q2-label">{{ __('Description') }}</span>
                                            <p class="q2-rail-memo" style="font-size:.8125rem;color:var(--muted,#5F7476)">{{ $order->memo }}</p>
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
                                            @foreach($order->lines as $line)
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
                                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval">{{ format_number($order->amount) }}</span></div>
                                        <div class="q2-srow"><span>{{ __('Tax') }}</span><span class="q2-sval">{{ format_number($order->tax_total) }}</span></div>
                                        <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval">{{ format_number($order->total) }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- attachments tab --}}
                        <section id="tab-files" class="q2-tab-panel">
                            @if($order->attachments->isNotEmpty())
                                <div class="q2-sec">
                                    <div class="q2-sec-head">
                                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                        <h2 class="q2-sec-title">{{ __('Attachments') }}</h2>
                                    </div>
                                    <ul class="q2-li-wrap">
                                        @foreach($order->attachments as $attachment)
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
                                    <p class="q2-empty">{{ __('No attachments for this sales order.') }}</p>
                                </div>
                            @endif
                        </section>
                    </div>
                </div>

                {{-- §4 rail --}}
                <aside class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">{{ __('Actions') }}</div>
                        <a href="{{ route('accounting.sales-orders.print', $order) }}" target="_blank" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Print / PDF') }}
                        </a>
                        @if($order->status === 'draft' && auth()->user()->can('sales-orders.edit'))
                            <a href="{{ route('accounting.sales-orders.edit', $order) }}" class="q2-vitem">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 3a2.8 2.8 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Edit Sales Order') }}
                            </a>
                        @endif
                        @if($order->status === 'fulfilled' && $order->converted_invoice_id)
                            <a href="{{ route('accounting.invoices.show', $order->convertedInvoice) }}" class="q2-vitem q2-link">{{ __('View Invoice') }}</a>
                        @endif
                        @if($order->status === 'fulfilled' && $order->converted_receipt_id)
                            <a href="{{ route('accounting.sales-receipts.show', $order->convertedReceipt) }}" class="q2-vitem q2-link">{{ __('View Sales Receipt') }}</a>
                        @endif
                        <a href="{{ route('accounting.sales-orders.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ __('Back to Sales Orders') }}
                        </a>
                        <div class="q2-rule"></div>
                        <a href="{{ route('accounting.reports.sales-by-customer') }}" class="q2-vitem q2-link">{{ __('Sales by Customer Report') }}</a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    {{-- convert to receipt modal --}}
    @if(in_array($order->status, ['sent', 'confirmed']) || ($order->status === 'fulfilled' && !$order->converted_receipt_id && !$order->converted_invoice_id))
        @can('sales-orders.convert')
            <x-modal name="convert-to-receipt" maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-ink">{{ __('Convert to Sales Receipt') }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('Record payment(s) and create a sales receipt for') }} {{ $order->sales_order_number }} ({{ $cs }}{{ format_number($order->total) }}).</p>

                    <form method="POST" action="{{ route('accounting.sales-orders.convert-to-receipt', $order) }}" id="so-convert-receipt-form" class="mt-4">
                        @csrf
                        <x-input-error :messages="$errors->get('error')" class="mb-4" />

                        <table class="q2-tbl">
                            <thead><tr>
                                <th>{{ __('Payment Method') }}</th>
                                <th class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                <th style="width:2.5rem"></th>
                            </tr></thead>
                            <tbody id="so-payments-body"></tbody>
                        </table>

                        <div class="mt-3">
                            <button type="button" id="so-add-payment" class="q2-btn q2-btn--ghost q2-btn--sm">＋ {{ __('Add Payment') }}</button>
                        </div>

                        <div class="mt-5 flex items-center justify-end gap-2">
                            <button type="button" class="q2-btn q2-btn--ghost" x-data x-on:click="$dispatch('close-modal', 'convert-to-receipt')">{{ __('Cancel') }}</button>
                            <button type="submit" class="q2-btn q2-btn--cta">{{ __('Create Sales Receipt') }}</button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endcan
    @endif

    <script>
        @if(in_array($order->status, ['sent', 'confirmed']) || ($order->status === 'fulfilled' && !$order->converted_receipt_id && !$order->converted_invoice_id))
        const SO_PAYMENT_METHODS = @json($paymentMethods->map(fn($pm) => ['id' => (int) $pm->id, 'name' => $pm->name])->values());
        @endif

        document.querySelectorAll('.q2-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.q2-tab').forEach(t => t.classList.remove('is-active'));
                tab.classList.add('is-active');
                document.querySelectorAll('.q2-tab-panel').forEach(p => {
                    p.style.display = (p.id === tab.dataset.target) ? '' : 'none';
                });
            });
        });

        function soPayRow(idx) {
            const methods = (typeof SO_PAYMENT_METHODS !== 'undefined' ? SO_PAYMENT_METHODS : [])
                .map(pm => `<option value="${pm.id}">${esc(pm.name)}</option>`).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><select name="payments[${idx}][payment_method_id]" class="q2-select" required>${methods}</select></td>
                <td><input type="number" step="0.01" min="0.01" name="payments[${idx}][amount]" class="q2-input q2-right" placeholder="0.00" required aria-label="{{ __('Payment amount') }}" /></td>
                <td><button type="button" class="q2-ibtn q2-ibtn--del" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}" onclick="this.closest('tr').remove()">🗑</button></td>`;
            return tr;
        }

        const soPaymentsBody = document.getElementById('so-payments-body');
        if (soPaymentsBody) {
            document.getElementById('so-add-payment').addEventListener('click', () => soPaymentsBody.appendChild(soPayRow(soPaymentsBody.children.length)));
            if (typeof SO_PAYMENT_METHODS !== 'undefined' && SO_PAYMENT_METHODS.length) {
                soPaymentsBody.appendChild(soPayRow(0));
            }
            document.getElementById('so-convert-receipt-form').addEventListener('submit', (e) => {
                const amounts = [...soPaymentsBody.querySelectorAll('[name*="[amount]"]')];
                const total = amounts.reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
                if (total <= 0) {
                    e.preventDefault();
                    CB && CB.toast('error', '{{ __('Enter at least one payment amount.') }}');
                }
            });
        }

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }
    </script>
</x-app-layout>
