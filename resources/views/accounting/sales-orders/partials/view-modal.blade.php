{{--
    APPENDIX A · Sales Order detail card — shared by the show page and the
    `?modal=1` standalone response (`SalesOrderController::show()`).

    Receives the show() viewData: order, paymentMethods, currencies,
    fulfilmentLines, can. Renders the full server-side detail (no JS
    population) so the modal and the show page can never disagree.

    Relying on the `.so2` suite = descendant selectors, the fragment is
    wrapped in a `.so2` ancestor when it stands alone (`?modal=1`); on the
    show page the parent already provides it. Class names are the bare suite
    vocabulary (btn, pill, mchip, fv-*, …).
--}}
@php
    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $order?->company_id ?: session('current_company_id'), '$');
    $isModal = request()->boolean('modal');
    $progress = $order->fulfilmentProgress();
    $status = $order->status;
@endphp

@if($isModal)
    <div class="so2">
    <div class="fv-scrim on" id="fvScrim">
        <div class="fv-wrap">
@endif

<div class="fvcard" id="so-fvcard">

    {{-- ── head: ref + status pill + actions ─────────────────────────────── --}}
    <div class="fv-head">
        <div class="fv-title">
            <span class="fv-label">{{ __('Sales Order') }}</span>
            <span class="fv-ref">{{ $order->sales_order_number }}</span>
            <span class="pill {{ $order->statusClass() }}"><i></i>{{ $order->statusLabel() }}</span>
        </div>
        <div class="fv-actions">
            @if($can['edit'] ?? false)
                <a href="{{ route('accounting.sales-orders.edit', $order) }}" class="btn btn-sec">{{ __('Edit') }}</a>
            @endif
            @if($can['print_pdf'] ?? false)
                <a href="{{ route('accounting.sales-orders.pdf', $order) }}" target="_blank" class="btn btn-ghost">{{ __('PDF') }}</a>
            @endif
            @if($can['send_email'] ?? false)
                <form method="POST" action="{{ route('accounting.sales-orders.send-email', $order) }}" class="inl"
                      onsubmit="return fbConfirmButton(event, '{{ __('Email this sales order to') }} {{ addslashes($order->customer?->name ?? $order->customer_name) }}?', { type: 'action' })">@csrf
                    <button type="submit" class="btn btn-ghost">{{ __('Email') }}</button>
                </form>
            @endif
            @if(! $isModal)
                <a href="{{ route('accounting.sales-orders.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
            @endif
        </div>
    </div>

    {{-- ── meta chips ─────────────────────────────────────────────────────── --}}
    <div class="fv-meta">
        <span class="mchip b">{{ $order->customer?->name ?? $order->customer_name ?? '—' }}</span>
        <span class="mchip">{{ __('Ordered') }} {{ $order->order_date?->format('M d, Y') ?? '—' }}</span>
        <span class="mchip">{{ __('Delivery') }} {{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</span>
        @if($can['fulfil'] ?? false)
            <span class="mchip mono">{{ number_format($progress['qty_fulfilled']) }} / {{ number_format($progress['qty_ordered']) }} {{ __('fulfilled') }}</span>
        @endif
        @if($order->source_quotation_id)
            <span class="mchip">{{ __('From quote') }} #{{ $order->sourceQuotation?->quotation_number ?? $order->source_quotation_id }}</span>
        @endif
        @if($order->convertedInvoice)
            <a class="mchip" href="{{ route('accounting.invoices.show', $order->convertedInvoice) }}">{{ __('Invoice') }} {{ $order->convertedInvoice->invoice_number }}</a>
        @endif
        @if($order->convertedReceipt)
            <a class="mchip" href="{{ route('accounting.sales-receipts.show', $order->convertedReceipt) }}">{{ __('Receipt') }} {{ $order->convertedReceipt->receipt_number }}</a>
        @endif
        @if($order->emailed_at)
            <span class="mchip mono">{{ __('Emailed') }} {{ $order->emailed_at?->format('M d, Y') }}</span>
        @endif
        <span class="mchip b">{{ __('Total') }} {{ $cs }}{{ format_number($order->total) }}</span>
    </div>

    {{-- ── body ───────────────────────────────────────────────────────────── --}}
    <div class="fv-body">

        <div class="fv-sec">
            <div class="fv-sechead">
                <span class="fv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <h3>{{ __('Line Items') }}</h3>
                <span class="fv-sec-count">{{ $order->lines->count() }}</span>
            </div>
            <div class="fv-table-wrap">
                <table class="fv-table">
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="r">{{ __('Qty') }}</th>
                            <th class="r">{{ __('Fulfilled') }}</th>
                            <th class="r">{{ __('Price') }}</th>
                            <th class="r">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->lines as $line)
                            <tr>
                                <td><span class="code">{{ $line->item_code ?: '—' }}</span></td>
                                <td>{{ $line->label() }}</td>
                                <td class="r">{{ number_format($line->quantity, 2) }}</td>
                                <td class="r">
                                    {{ number_format((float) $line->qty_fulfilled, 2) }}
                                    @if((float) $line->qty_fulfilled > 0 && $line->remaining() > 0)
                                        <span class="xc soon">{{ __('part') }}</span>
                                    @endif
                                </td>
                                <td class="r num">{{ format_number($line->unit_price) }}</td>
                                <td class="r num">{{ format_number($line->line_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="fv-empty">{{ __('No line items.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="fv-totals">
                <div class="fv-trow"><span>{{ __('Subtotal') }}</span><span class="num">{{ $cs }}{{ format_number($order->amount) }}</span></div>
                @if((float) $order->tax_total > 0)
                    <div class="fv-trow"><span>{{ __('Tax') }}</span><span class="num">{{ $cs }}{{ format_number($order->tax_total) }}</span></div>
                @endif
                <div class="fv-trow gt"><span>{{ __('Grand Total') }}</span><span class="num">{{ $cs }}{{ format_number($order->total) }}</span></div>
            </div>
        </div>

        <div class="fv-sec">
            <div class="fv-sechead">
                <span class="fv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <h3>{{ __('Order Details') }}</h3>
            </div>
            <div class="fv-grid">
                @if($order->contact_person)
                    <div class="field"><span class="flabel">{{ __('Contact') }}</span><span class="fval">{{ $order->contact_person }}</span></div>
                @endif
                @if($order->email)
                    <div class="field"><span class="flabel">{{ __('Email') }}</span><span class="fval">{{ $order->email }}</span></div>
                @endif
                @if($order->phone)
                    <div class="field"><span class="flabel">{{ __('Phone') }}</span><span class="fval">{{ $order->phone }}</span></div>
                @endif
                @if($order->delivery_address)
                    <div class="field"><span class="flabel">{{ __('Delivery address') }}</span><span class="fval">{{ $order->delivery_address }}</span></div>
                @endif
                @if($order->delivery_method)
                    <div class="field"><span class="flabel">{{ __('Delivery method') }}</span><span class="fval">{{ $order->delivery_method }}</span></div>
                @endif
                <div class="field"><span class="flabel">{{ __('Delivery date') }}</span><span class="fval">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</span></div>
                <div class="field"><span class="flabel">{{ __('Warehouse / Branch') }}</span><span class="fval">{{ $order->branch?->name ?? '—' }}</span></div>
                @if($order->currency)
                    <div class="field"><span class="flabel">{{ __('Currency') }}</span><span class="fval">{{ $order->currency }}</span></div>
                @endif
                @if($order->reference)
                    <div class="field"><span class="flabel">{{ __('Reference') }}</span><span class="fval mono">{{ $order->reference }}</span></div>
                @endif
                @if($order->memo)
                    <div class="field wide"><span class="flabel">{{ __('Notes') }}</span><span class="fval">{{ $order->memo }}</span></div>
                @endif
            </div>
        </div>

        @if($order->status === 'fulfilled' || $order->delivered_on)
            <div class="fv-sec">
                <div class="fv-sechead">
                    <span class="fv-sec-ic">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <h3>{{ __('Fulfilment') }}</h3>
                </div>
                <div class="fv-grid">
                    @if($order->delivered_on)
                        <div class="field"><span class="flabel">{{ __('Delivered') }}</span><span class="fval">{{ $order->delivered_on?->format('M d, Y') ?? '—' }}</span></div>
                    @endif
                    @if($order->delivery_note_no)
                        <div class="field"><span class="flabel">{{ __('Delivery note') }}</span><span class="fval mono">{{ $order->delivery_note_no }}</span></div>
                    @endif
                    @if($order->received_by)
                        <div class="field"><span class="flabel">{{ __('Received by') }}</span><span class="fval">{{ $order->received_by }}</span></div>
                    @endif
                </div>
            </div>
        @endif

        @if($order->cancel_reason)
            <div class="fv-note warn">{{ $order->cancel_reason }}</div>
        @endif
        @if($order->void_reason)
            <div class="fv-note red">{{ __('Voided') }}: {{ $order->void_reason }}</div>
        @endif
    </div>

    {{-- ── foot: per-status actions + close ───────────────────────────────── --}}
    <div class="fv-foot">
        <div class="fv-foot-actions">
            @if($status === 'draft')
                @if($can['release'] ?? false)
                    <form method="POST" action="{{ route('accounting.sales-orders.release', $order) }}" class="inl"
                          onsubmit="return fbConfirmButton(event, '{{ __('Release this sales order? It will be emailed to the customer.') }}', { type: 'action' })">@csrf
                        <button type="submit" class="btn btn-cta">{{ __('Release') }}</button>
                    </form>
                @endif
                @if($can['cancel'] ?? false)
                    <form method="POST" action="{{ route('accounting.sales-orders.cancel', $order) }}" class="inl"
                          onsubmit="return fbConfirmButton(event, '{{ __('Cancel this draft sales order?') }}', { type: 'danger' })">@csrf
                        <button type="submit" class="btn btn-danger-o">{{ __('Cancel') }}</button>
                    </form>
                @endif
                @if($can['void'] ?? false)
                    <form method="POST" action="{{ route('accounting.sales-orders.void', $order) }}" class="inl"
                          onsubmit="return fbPromptForm(event, '{{ __('Enter a void reason') }}:')">@csrf
                        <button type="submit" class="btn btn-danger-o">{{ __('Void') }}</button>
                    </form>
                @endif
            @elseif($status === 'sent')
                @if($can['confirm'] ?? false)
                    <form method="POST" action="{{ route('accounting.sales-orders.confirm', $order) }}" class="inl"
                          onsubmit="return fbConfirmButton(event, '{{ __('Confirm this sales order?') }}', { type: 'action' })">@csrf
                        <button type="submit" class="btn btn-cta">{{ __('Confirm') }}</button>
                    </form>
                @endif
                @if($can['cancel'] ?? false)
                    <form method="POST" action="{{ route('accounting.sales-orders.cancel', $order) }}" class="inl"
                          onsubmit="return fbConfirmButton(event, '{{ __('Cancel this sales order?') }}', { type: 'danger' })">@csrf
                        <button type="submit" class="btn btn-danger-o">{{ __('Cancel') }}</button>
                    </form>
                @endif
            @elseif($status === 'confirmed')
                @if($can['fulfil'] ?? false)
                    <button type="button" class="btn btn-cta"
                            onclick="window.dispatchEvent(new CustomEvent('so:open-fulfil', { detail: { url: '{{ route('accounting.sales-orders.show', $order) }}?fulfil=1' } }))">{{ __('Fulfil delivery') }}</button>
                @endif
            @elseif($status === 'fulfilled')
                @if(! $order->convertedReceipt && ! $order->convertedInvoice && ($can['invoice'] ?? false))
                    <form method="POST" action="{{ route('accounting.sales-orders.invoice', $order) }}" class="inl"
                          onsubmit="return fbConfirmButton(event, '{{ __('Create an invoice from this sales order?') }}', { type: 'action' })">@csrf
                        <button type="submit" class="btn btn-cta">{{ __('Create invoice') }}</button>
                    </form>
                @endif
                @if($order->convertedInvoice)
                    <a href="{{ route('accounting.invoices.show', $order->convertedInvoice) }}" class="btn btn-sec">{{ __('View invoice') }}</a>
                @endif
                @if($order->convertedReceipt)
                    <a href="{{ route('accounting.sales-receipts.show', $order->convertedReceipt) }}" class="btn btn-sec">{{ __('View receipt') }}</a>
                @endif
            @endif

            @if($can['print_pdf'] ?? false)
                <a href="{{ route('accounting.sales-orders.print', $order) }}" target="_blank" class="btn btn-ghost">{{ __('Print') }}</a>
            @endif
        </div>
        @if($isModal)
            <button type="button" class="btn-close" data-close="fvScrim">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                {{ __('Close') }}
            </button>
        @endif
    </div>
</div>

@if($isModal)
        </div>
    </div>
    </div>
@endif

@if($isModal)
<script>
    (function () {
        const scrim = document.getElementById('fvScrim');
        if (! scrim) return;
        const close = (el) => {
            const card = el.closest('.fv-scrim');
            if (card) card.classList.remove('on');
        };
        scrim.querySelectorAll('[data-close="fvScrim"]').forEach(b => b.addEventListener('click', () => close(b)));
        scrim.addEventListener('keydown', (e) => { if (e.key === 'Escape') scrim.classList.remove('on'); });
    })();
</script>
@endif