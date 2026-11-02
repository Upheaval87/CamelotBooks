<x-app-layout>
    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="so2">

                {{-- sticky page head --}}
                <div class="page-head" style="position:sticky;top:var(--topbar-h,106px);z-index:40;background:rgba(245,247,252,.9);backdrop-filter:blur(12px)">
                    <div>
                        <h1 style="font-size:1.75rem;font-weight:800;letter-spacing:-.02em;color:#111827">
                            {{ __('Sales Order') }} <span class="mono">{{ $order->sales_order_number }}</span>
                        </h1>
                        <div class="osub">
                            {{ $order->customer?->name ?? $order->customer_name ?? __('No customer') }} ·
                            {{ __('ordered') }} {{ $order->order_date?->format('M d, Y') ?? '—' }} ·
                            {{ __('delivery') }} {{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}
                        </div>
                    </div>
                    <a href="{{ route('accounting.sales-orders.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>

                <div class="shell">

                    {{-- ── the shared detail card (show page → non-modal) ────────── --}}
                    @include('accounting.sales-orders.partials.view-modal', [
                        'order' => $order,
                        'can' => $can,
                        'cs' => $cs,
                    ])

                    {{-- decision / outcome strip positioned under the card --}}
                    @if($order->status === 'sent')
                        <div class="card" style="margin-top:16px">
                            <div class="card-h" style="margin-bottom:6px">{{ __('Review & Confirm') }}</div>
                            <p style="font-size:12.5px;color:var(--s-muted, #5f7476)">{{ __('Confirm this order to commit the sale, or cancel it.') }}</p>
                            <div style="display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px;align-items:center;border-top:1px solid var(--s-line, #e2ecec);margin-top:14px;padding-top:14px">
                                <form id="so-cancel-form" method="POST" action="{{ route('accounting.sales-orders.cancel', $order) }}" class="inl"
                                      onsubmit="return fbConfirmButton(event, '{{ __('Cancel this sales order?') }}', { type: 'danger' })">@csrf</form>
                                <form id="so-confirm-form" method="POST" action="{{ route('accounting.sales-orders.confirm', $order) }}" class="inl"
                                      onsubmit="return fbConfirmButton(event, '{{ __('Confirm this sales order?') }}', { type: 'action' })">@csrf</form>
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

                    {{-- conversion card --}}
                    @if($order->fulfilments->isNotEmpty())
                        <div class="card" style="margin-top:16px">
                            <div class="card-h">{{ __('Fulfilment History') }}</div>
                            <div class="tbl-wrap">
                                <table class="tbl">
                                    <thead><tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Delivery note') }}</th>
                                        <th class="r">{{ __('Qty fulfilled') }}</th>
                                        <th>{{ __('Received by') }}</th>
                                        <th>{{ __('Recorded by') }}</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach($order->fulfilments as $f)
                                            <tr>
                                                <td>{{ $f->date?->format('M d, Y') ?? '—' }}</td>
                                                <td class="mono">{{ $f->delivery_note_no ?? '—' }}</td>
                                                <td class="r num">{{ number_format((float) $f->qty_fulfilled, 2) }}</td>
                                                <td>{{ $f->received_by ?? '—' }}</td>
                                                <td>{{ $f->createdByUser?->name ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($order->status === 'fulfilled' && ($order->converted_invoice_id || $order->converted_receipt_id))
                        <div class="card" style="margin-top:16px">
                            <div class="card-h">{{ __('Conversion') }}</div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
                                @if($order->converted_invoice_id)
                                    <div class="field"><span class="flabel">{{ __('Invoice') }}</span>
                                        <a href="{{ route('accounting.invoices.show', $order->convertedInvoice) }}" class="link mono">{{ $order->convertedInvoice->invoice_number ?? '—' }}</a></div>
                                @endif
                                @if($order->converted_receipt_id)
                                    <div class="field"><span class="flabel">{{ __('Sales Receipt') }}</span>
                                        <a href="{{ route('accounting.sales-receipts.show', $order->convertedReceipt) }}" class="link mono">{{ $order->convertedReceipt->receipt_number ?? '—' }}</a></div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- attachments --}}
                    @if($order->attachments->isNotEmpty())
                        <div class="card" style="margin-top:16px">
                            <div class="card-h">{{ __('Attachments') }}</div>
                            <div class="fv-table-wrap">
                                <table class="fv-table">
                                    <thead><tr>
                                        <th>{{ __('File') }}</th>
                                        <th class="r">{{ __('Size') }}</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach($order->attachments as $attachment)
                                            <tr>
                                                <td><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path) }}" target="_blank" class="link">{{ $attachment->name }}</a></td>
                                                <td class="r num">{{ format_bytes($attachment->file_size) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- fulfil modal (auto-opens on ?fulfil=1) --}}
    @include('accounting.sales-orders.partials.fulfil-modal', [
        'order' => $order,
        'fulfilmentLines' => $fulfilmentLines,
        'can' => $can,
        'cs' => $cs,
    ])

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

    @php
        $showReceipt = in_array($order->status, ['sent', 'confirmed']) || ($order->status === 'fulfilled' && !$order->converted_receipt_id && !$order->converted_invoice_id);
        $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp
    <script>
        window.addEventListener('so:open-fulfil', function (e) {
            const scrim = document.getElementById('fulScrim');
            if (scrim) { scrim.classList.add('on'); return; }
            if (e.detail && e.detail.url) { window.location.href = e.detail.url; }
        });

        @if($showReceipt)
        const SO_PAYMENT_METHODS = @json($paymentMethods->map(fn($pm) => ['id' => (int) $pm->id, 'name' => $pm->name])->values());
        @endif

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

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
    </script>
</x-app-layout>