@php
    $salesReceipt = $salesReceipt ?? null;
    $isEdit = $isEdit ?? (bool) $salesReceipt;
    $formAction = $formAction ?? ($isEdit ? route('accounting.sales-receipts.update', $salesReceipt) : route('accounting.sales-receipts.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.sales-receipts.show', $salesReceipt) : route('accounting.sales-receipts.index'));
    $title = $title ?? ($isEdit ? 'Edit Sales Receipt' : 'Create Sales Receipt');
    $submitLabel = $submitLabel ?? ($isEdit ? 'Update Receipt' : 'Save');

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $defaultIncomeAccountId = $defaultIncomeAccountId ?? ($incomeAccounts->first()?->id ?? '');

    $oldCustomerId = old('customer_id', $salesReceipt?->customer_id ?? ($selectedCustomerId ?? ''));
    $selectedCustomer = $customers->firstWhere('id', (int) $oldCustomerId);

    $selectedBranchId = old('branch_id', $salesReceipt?->branch_id ?? '');
    $selectedBranchLabel = $selectedBranchId ? ($branches->firstWhere('id', (int) $selectedBranchId)?->name ?? '') : '';

    if ($isEdit) {
        $customerLine = $salesReceipt?->customer?->name ?? __('Walk-in');
        $receiptDate = $salesReceipt?->receipt_date?->format('M d, Y') ?? '—';
        $subtitle = $subtitle ?? "{$customerLine} · " . __('receipt') . " {$receiptDate}";
    } else {
        $subtitle = $subtitle ?? __('Record a customer payment with an itemised breakdown.');
    }

    $linesData = [];
    if (old('lines')) {
        foreach (array_values(old('lines')) as $l) {
            $gross = ((float) ($l['quantity'] ?? 0)) * ((float) ($l['unit_price'] ?? 0));
            $flat = (float) ($l['discount'] ?? 0);
            $linesData[] = [
                'product_id' => $l['product_id'] ?? '',
                'label' => $products->firstWhere('id', (int) ($l['product_id'] ?? 0))?->name ?? '',
                'sku' => $products->firstWhere('id', (int) ($l['product_id'] ?? 0))?->sku ?? '',
                'description' => $l['description'] ?? '',
                'quantity' => (float) ($l['quantity'] ?? 1),
                'unit_price' => (float) ($l['unit_price'] ?? 0),
                'discount' => $flat,
                'discount_pct' => $gross > 0 ? round($flat / $gross * 100, 2) : 0,
                'tax_rate' => (float) ($l['tax_rate'] ?? 0),
                'income_account_id' => $l['income_account_id'] ?? $defaultIncomeAccountId,
            ];
        }
    } elseif ($salesReceipt) {
        $linesData = $salesReceipt->lines->map(function ($l) {
            $gross = ((float) $l->quantity) * ((float) $l->unit_price);
            $flat = (float) $l->discount;

            return [
                'product_id' => $l->product_id ?? '',
                'label' => $l->product?->name ?? '',
                'sku' => $l->product?->sku ?? '',
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount' => $flat,
                'discount_pct' => $gross > 0 ? round($flat / $gross * 100, 2) : 0,
                'tax_rate' => (float) $l->tax_rate,
                'income_account_id' => $l->income_account_id,
            ];
        })->all();
    }

    $paymentsData = [];
    if (old('payments')) {
        foreach (array_values(old('payments')) as $p) {
            $pm = $paymentMethods->firstWhere('id', (int) ($p['payment_method_id'] ?? 0));
            $paymentsData[] = [
                'payment_method_id' => $p['payment_method_id'] ?? '',
                'amount' => (float) ($p['amount'] ?? 0),
                'cash_tendered' => $p['cash_tendered'] ?? '',
                'reference_number' => $p['reference_number'] ?? '',
                'institution' => $p['institution'] ?? '',
                'type' => $pm?->type ?? '',
            ];
        }
    } elseif ($salesReceipt) {
        foreach ($salesReceipt->payments as $p) {
            $paymentsData[] = [
                'payment_method_id' => $p->payment_method_id,
                'amount' => (float) $p->amount,
                'cash_tendered' => $p->cash_tendered ?? '',
                'reference_number' => $p->reference_number ?? '',
                'institution' => $p->institution ?? '',
                'type' => $p->paymentMethod?->type ?? '',
            ];
        }
    }
@endphp

<div class="sr-suite pb-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

        {{-- §2/§3 sticky page head --}}
        <div class="sticky-head">
            <div>
                <h1>{{ $title }}
                    @if ($isEdit)
                        <span class="mono-chip">{{ $salesReceipt->receipt_number }}</span>
                        <span class="badge b-draft"><span class="bdot"></span>{{ __('Draft') }}</span>
                    @endif
                </h1>
                <div class="sub">{{ $subtitle }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                @if(!$isEdit)
                    <a href="{{ route('accounting.sales-receipts.index') }}" class="btn btn-ghost btn-sm">← {{ __('Back') }}</a>
                @endif
                <a href="{{ $cancelRoute }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
                @if($isEdit)
                    @if($salesReceipt->created_by && (int) $salesReceipt->created_by !== (int) auth()->id())
                        <button type="submit" form="receipt-delete-form" class="btn btn-danger-o btn-sm">{{ __('Delete') }}</button>
                    @endif
                    <div class="seg">
                        <button type="submit" name="action" value="save" form="receipt-form" class="btn btn-sec">{{ $submitLabel }}</button>
                        <button type="submit" name="action" value="save_and_post" form="receipt-form" class="btn btn-cta">
                            {{ __('Save & Post') }} <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                @else
                    <button type="button" id="sr-invoice-btn" class="btn btn-cta-inv">{{ __('Receipt from Invoice') }}</button>
                    <button type="submit" name="action" value="save_and_new" form="receipt-form" class="btn btn-ghost btn-sm">{{ __('Save & New') }}</button>
                    <div class="seg">
                        <button type="submit" name="action" value="save_draft" form="receipt-form" class="btn btn-ghost btn-sm">{{ __('Save Draft') }}</button>
                        <button type="submit" name="action" value="save" form="receipt-form" class="btn btn-sec">{{ $submitLabel }}</button>
                        <button type="submit" name="action" value="save_and_post" form="receipt-form" class="btn btn-cta">
                            {{ __('Save & Post') }} <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" id="receipt-form" class="sr-form" novalidate data-customer-name="{{ $selectedCustomer?->name ?? '' }}">
            @csrf
            @if ($formMethod === 'PUT')
                @method('PUT')
            @endif

            <input type="hidden" id="invoice_id" name="invoice_id" value="{{ old('invoice_id', $preselectInvoiceId ?? $salesReceipt?->invoice_id ?? '') }}" />
            <input type="hidden" id="sr-settlement-invoice-label" name="sr_settlement_invoice_label" value="" />

            <x-input-error :messages="$errors->get('error')" class="mb-4" />
            <x-input-error :messages="$errors->get('invoice_id')" class="mb-4" />

            <div class="shell{{ $isEdit ? '' : ' shell--wide' }}">
                <section class="card">

                    {{-- §2/§3 receipt details --}}
                    <div class="card-sec">
                        <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Receipt Details') }}</h2><span class="rule"></span></div>
                        <div class="g4" style="margin-top:1.25rem">
                            <div class="field sp2">
                                <label>{{ __('Customer (optional)') }}</label>
                                <x-scoped-search-field
                                    name="customer_id"
                                    entity="customer"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                    :value="old('customer_id', $oldCustomerId)"
                                    :label="old('customer_name', $selectedCustomer?->name ?? '')"
                                    placeholder="{{ __('Search customers…') }}"
                                    on-select="srCustomerSelected"
                                />
                                <x-input-error :messages="$errors->get('customer_id')" />
                                <div class="hint">{{ __('Leave empty for walk-in cash sales.') }}</div>
                            </div>
                            <div class="field">
                                <label>{{ __('Receipt Date') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                                <input id="receipt_date" name="receipt_date" type="date" class="input h44" value="{{ old('receipt_date', $salesReceipt?->receipt_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                                <x-input-error :messages="$errors->get('receipt_date')" />
                            </div>
                            <div class="field">
                                <label>{{ __('Reference') }}</label>
                                <input id="reference" name="reference" type="text" class="input h44" value="{{ old('reference', $salesReceipt?->reference ?? '') }}" placeholder="{{ __('Optional reference') }}" />
                                <x-input-error :messages="$errors->get('reference')" />
                            </div>
                            <div class="field">
                                <label>{{ __('Branch') }}</label>
                                <x-scoped-search-field
                                    name="branch_id"
                                    entity="branch"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                    :value="old('branch_id', $selectedBranchId)"
                                    :label="old('branch_id', $selectedBranchLabel)"
                                    placeholder="{{ __('None') }}"
                                />
                                <x-input-error :messages="$errors->get('branch_id')" />
                            </div>
                            <div class="field sp3">
                                <label>{{ __('Description') }}</label>
                                <input id="memo" name="memo" type="text" class="input h44" value="{{ old('memo', $salesReceipt?->memo ?? '') }}" placeholder="{{ __('Optional memo') }}" />
                                <x-input-error :messages="$errors->get('memo')" />
                            </div>
                        </div>
                    </div>

                    {{-- §2/§3 payments (ABOVE line items per mockup) --}}
                    <div class="card-sec">
                        <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Payments') }}</h2><span class="rule"></span>
                            <button type="button" id="sr-add-payment" class="btn btn-ghost btn-sm" style="margin-left:12px">＋ {{ __('Add Payment') }}</button></div>
                        <div id="sr-payments-body" style="display:flex;flex-direction:column;gap:0.75rem;margin-top:1.25rem"></div>
                        <x-input-error :messages="$errors->get('payments')" class="mt-2" />
                    </div>

                    {{-- §2/§3 line items --}}
                    <div class="card-sec sr-standalone" id="sr-lines-section">
                        <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Line Items') }}</h2><span class="rule"></span>
                            <button type="button" id="sr-add-line" class="btn btn-ghost btn-sm" style="margin-left:12px">＋ {{ __('Add Line') }}</button></div>
                        <div class="li-wrap" style="margin-top:1rem">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:7rem">{{ __('Code') }}</th>
                                        <th style="min-width:11rem">{{ __('Product') }}</th>
                                        <th style="min-width:10rem">{{ __('Description') }}</th>
                                        <th class="num" style="width:5.5rem">{{ __('Qty') }}</th>
                                        <th class="num" style="width:6.5rem">{{ __('Price') }} ({{ $cs }})</th>
                                        <th class="num" style="width:5.5rem">{{ __('Disc %') }}</th>
                                        <th style="min-width:11rem">{{ __('Income Account') }}</th>
                                        <th class="num" style="width:6.5rem">{{ __('Line Total') }}</th>
                                        <th style="width:3.5rem"></th>
                                    </tr>
                                </thead>
                                <tbody id="sr-lines-body"></tbody>
                            </table>
                        </div>
                        <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                    </div>

                    {{-- Settlement-only panel (shown when an invoice is linked) --}}
                    <div class="card-sec sr-settled" id="sr-settlement-card" style="display:none">
                        <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M4 9h16M9 4v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Invoice Settlement') }}</h2><span class="rule"></span>
                            <button type="button" id="sr-clear-invoice" class="btn btn-ghost btn-sm" style="margin-left:12px">{{ __('× Clear') }}</button></div>
                        <div class="settle-panel">
                            <div class="sp-kicker">{{ __('Receipt from Invoice') }}</div>
                            <div class="sp-inv" id="sp-invoice">—</div>
                            <div class="sp-meta" id="sp-customer">—</div>
                            <div class="sp-divider"></div>
                            <div class="sp-row"><span>{{ __('Invoice Amount') }}</span><span class="v" id="sp-amount">0.00</span></div>
                            <div class="sp-row"><span>{{ __('Already Paid') }}</span><span class="v" id="sp-paid">0.00</span></div>
                            <div class="sp-row strong"><span>{{ __('Outstanding Balance') }}</span><span class="v" id="sp-balance">0.00</span></div>
                            <div class="sp-divider"></div>
                            <div class="sp-row"><span>{{ __('Applying Received') }}</span><span class="v" id="sp-applied">0.00</span></div>
                            <div class="sp-row" id="sp-over-row" style="display:none"><span class="sp-over-label">{{ __('Overpayment') }}</span><span class="v sp-over-val" id="sp-over">0.00</span></div>
                            <div class="sp-row sp-status-row"><span>{{ __('Resulting Status') }}</span><span class="v" id="sp-status"></span></div>
                            <div class="sp-note" id="sp-note">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 8v5m0 3h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                <span>Payments received will be applied against this invoice's outstanding balance.</span>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('payments')" class="mt-2" />
                    </div>

                    {{-- §2/§3 totals --}}
                    <div class="card-sec sr-standalone" id="sr-totals-section">
                        <div class="li-totals" style="margin-top:0"><div class="box">
                            <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v" id="v-sub">0.00</span></div>
                            <div class="trow" id="r-disc" style="display:none"><span>{{ __('Discount') }}</span><span class="v" id="v-disc">0.00</span></div>
                            <div class="trow" id="r-tax" style="display:none"><span>{{ __('Tax') }}</span><span class="v" id="v-tax">0.00</span></div>
                            <div class="trow"><span>{{ __('Total Payments') }}</span><span class="v" id="v-pay">0.00</span></div>
                            <div class="trow total"><span>{{ __('Total') }}</span><span class="v" id="v-gt">{{ $cs }}0.00</span></div>
                        </div></div>
                    </div>
                </section>

                @if($isEdit)
                {{-- §2/§3 rail --}}
                <aside class="railsum">
                    <section class="card">
                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7.5h8M8.5 12h.01M12 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ $isEdit ? __('Breakdown') : __('Summary') }}</h2></div>
                            <div style="margin-top:8px">
                                <div class="srow"><span class="l">{{ __('Customer') }}</span><span class="v" id="p-cust">{{ $selectedCustomer?->name ?? __('Walk-in') }}</span></div>
                                <div class="srow" id="p-inv-row" style="display:none"><span class="l">{{ __('Invoice') }}</span><span class="v" id="p-inv">—</span></div>
                                <div class="srow"><span class="l">{{ __('Date') }}</span><span class="v" id="p-date">{{ $salesReceipt?->receipt_date?->format('M d, Y') ?? now()->format('M d, Y') }}</span></div>
                                <div style="height:6px"></div>
                                <div class="srow sr-standalone"><span class="l">{{ __('Subtotal') }}</span><span class="v" id="p-sub">0.00</span></div>
                                @if($isEdit)
                                    <div class="srow sr-standalone"><span class="l">{{ __('Tax') }}</span><span class="v" id="p-tax">0.00</span></div>
                                    <div class="srow strong sr-standalone"><span class="l">{{ __('Total') }}</span><span class="v" id="p-total">0.00</span></div>
                                    <div class="srow sr-standalone"><span class="l">{{ __('Received') }}</span><span class="v" id="p-received">0.00</span></div>
                                @else
                                    <div class="srow sr-standalone"><span class="l">{{ __('Payments') }}</span><span class="v" id="p-pay">0.00</span></div>
                                @endif
                                <div class="srow sr-settled" style="display:none"><span class="l">{{ __('Applied to Invoice') }}</span><span class="v" id="p-applied">0.00</span></div>
                                <div class="srow sr-settled" style="display:none" id="p-over-row"><span class="l">{{ __('Overpayment') }}</span><span class="v" id="p-over">0.00</span></div>
                            </div>
                            <div class="gt"><span class="l">{{ $isEdit ? __('Total Received') : __('Total') }}</span><span class="v" id="p-gt">{{ $cs }}0.00</span></div>
                        </div>

                        <div class="rail-sec">
                            <div class="sec-head"><span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                            <div class="vlist">
                                @if ($isEdit)
                                    <a href="{{ route('accounting.sales-receipts.show', $salesReceipt) }}" class="vitem">
                                        <span class="ic">👤</span>{{ __('View Receipt') }}
                                    </a>
                                    @if($salesReceipt->status === 'draft' && auth()->user()->can('sales-receipts.post'))
                                        <a href="{{ route('accounting.sales-receipts.post-page', $salesReceipt) }}" class="vitem">
                                            <span class="ic">📤</span>{{ __('Post Receipt') }}
                                        </a>
                                    @endif
                                    <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" rel="noopener" class="vitem">
                                        <span class="ic">🖨</span>{{ __('Print / PDF') }}
                                    </a>
                                    @if ($salesReceipt?->customer?->email)
                                        <form method="POST" action="{{ route('accounting.sales-receipts.email', $salesReceipt) }}">
                                            @csrf
                                            <button type="submit" class="vitem">
                                                <span class="ic">✉</span>{{ __('Email Receipt') }}
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('accounting.sales-receipts.index') }}" class="vitem">
                                        <span class="ic">←</span>{{ __('All Receipts') }}
                                    </a>
                                @else
                                    <a href="{{ route('accounting.sales-receipts.index') }}" class="vitem">
                                        <span class="ic">📒</span>{{ __('Receipts List') }}
                                    </a>
                                    <a href="{{ route('accounting.customers.create') }}" class="vitem">
                                        <span class="ic">👤</span>{{ __('New Customer') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </section>
                </aside>
                @endif
            </div>
        </form>

        @if($isEdit && $salesReceipt->created_by && (int) $salesReceipt->created_by !== (int) auth()->id())
        <form id="receipt-delete-form" method="POST" action="{{ route('accounting.sales-receipts.destroy', $salesReceipt) }}" onsubmit="return fbConfirmSubmit(event, 'Delete this draft receipt? This cannot be undone.', { type: 'danger' })">
            @csrf
            @method('DELETE')
        </form>
        @endif

        {{-- Receipt from Invoice — locate outstanding invoice modal --}}
        <div class="sr-modal-overlay" id="sr-invoice-modal" hidden>
            <div class="sr-modal" role="dialog" aria-modal="true" aria-labelledby="sr-invoice-modal-title">
                <div class="sr-modal-head">
                    <div>
                        <div class="sr-modal-eyebrow">{{ __('Receipt from Invoice') }}</div>
                        <h2 id="sr-invoice-modal-title">{{ __('Locate Outstanding Invoice') }}</h2>
                    </div>
                    <button type="button" class="sr-modal-close" id="sr-invoice-close" aria-label="{{ __('Close') }}">✕</button>
                </div>
                <div class="sr-modal-search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <input id="sr-invoice-search" type="text" placeholder="{{ __('Search by invoice number, reference or customer…') }}" autocomplete="off" />
                </div>
                <div class="sr-invoice-filters">
                    <label for="sr-invoice-customer-ctx" class="sr-filter-chip" id="sr-customer-chip">
                        <span id="sr-customer-filter-label">{{ __('Any customer') }}</span>
                        <button type="button" id="sr-customer-filter-clear" title="{{ __('Clear') }}" aria-label="{{ __('Clear customer filter') }}">✕</button>
                    </label>
                </div>
                <div class="sr-invoice-list" id="sr-invoice-list" role="listbox">
                    <div class="sr-invoice-empty" id="sr-invoice-empty">{{ __('Search to find outstanding invoices…') }}</div>
                </div>
                <div class="sr-modal-foot">
                    <span class="sr-modal-count" id="sr-invoice-count"></span>
                    <button type="button" class="btn btn-ghost btn-sm" id="sr-invoice-cancel">{{ __('Cancel') }}</button>
                </div>
            </div>
        </div>

<script>
    const SR_INVOICE_URL = @json(route('accounting.sales-receipts.locate-invoices'));

    (function () {
        const overlay = document.getElementById('sr-invoice-modal');
        const list = document.getElementById('sr-invoice-list');
        const searchInput = document.getElementById('sr-invoice-search');
        const empty = document.getElementById('sr-invoice-empty');
        const countEl = document.getElementById('sr-invoice-count');
        const customerFilterLabel = document.getElementById('sr-customer-filter-label');
        const customerClearBtn = document.getElementById('sr-customer-filter-clear');

        let open = false;
        let debounceTimer = null;
        let activeInvoices = [];

        function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

        function statusLabel(st) {
            return ({ sent: 'Sent', partially_paid: 'Partially Paid', overdue: 'Overdue' })[st] || st;
        }

        function openModal(customerId) {
            open = true;
            overlay.hidden = false;
            if (!customerId) {
                customerFilterLabel.textContent = 'Any customer';
                customerClearBtn.style.display = 'none';
            } else {
                customerFilterLabel.textContent = customerId ? ('Customer #' + customerId) : 'Any customer';
                customerClearBtn.style.display = 'inline-flex';
            }
            searchInput.value = '';
            searchInput.focus();
            fetchInvoices();
        }

        function closeModal() {
            open = false;
            overlay.hidden = true;
        }

        async function fetchInvoices() {
            const params = new URLSearchParams({ q: searchInput.value.trim() });
            const chip = customerFilterLabel.textContent;
            if (chip && chip !== 'Any customer') {
                const id = chip.replace(/[^0-9]/g, '');
                if (id) params.set('customer_id', id);
            }
            try {
                const res = await fetch(SR_INVOICE_URL + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('Bad status');
                const data = await res.json();
                renderList(data.invoices || []);
            } catch (err) {
                list.innerHTML = '<div class="sr-invoice-empty">Unable to load invoices.</div>';
                countEl.textContent = '';
            }
        }

        function renderList(invoices) {
            activeInvoices = invoices;
            if (!invoices.length) {
                list.innerHTML = '<div class="sr-invoice-empty">No outstanding invoices found.</div>';
                countEl.textContent = '0 found';
                return;
            }
            list.innerHTML = invoices.map(i => `
                <button type="button" class="sr-invoice-row" role="option" data-id="${i.id}"
                    data-number="${esc(i.invoice_number)}" data-customer="${esc(i.customer_name)}"
                    data-customer-id="${i.customer_id}" data-amount="${i.amount}" data-paid="${i.amount_paid}"
                    data-balance="${i.balance}" data-date="${esc(i.invoice_date)}">
                    <span class="ir-mon" data-ir-text>${esc(i.invoice_number)}</span>
                    <span class="ir-main">
                        <span class="ir-name">${esc(i.customer_name)}</span>
                        <span class="ir-meta">${fmtDate(i.invoice_date)} · ${statusLabel(i.status)}</span>
                    </span>
                    <span class="ir-bal">${SR_CS}${fmt(i.balance)}</span>
                </button>
            `).join('');
            countEl.textContent = invoices.length + ' found';
        }

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchInvoices, 250);
        });

        list.addEventListener('click', e => {
            const row = e.target.closest('[data-id]');
            if (!row) return;
            selectInvoice(row);
            closeModal();
        });

        list.addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;
            const row = e.target.closest('[data-id]');
            if (row) { selectInvoice(row); closeModal(); }
        });

        function selectInvoice(row) {
            const d = row.dataset;
            const invoiceId = d.id;
            const invoiceNumber = d.number;
            const customerId = d.customerId || '';
            const customerName = d.customer;

            document.getElementById('invoice_id').value = invoiceId;
            document.getElementById('sr-settlement-invoice-label').value = invoiceNumber;

            // Prefill the customer (only if one is set on the invoice).
            if (customerId) {
                const custInput = document.querySelector('[name="customer_id"][x-ref]') || document.querySelector('[name="customer_id"]');
                if (custInput) {
                    // scoped-search-field setter
                    scopedSearchFieldSet(custInput, 'customer', { id: customerId, label: customerName, name: customerName });
                }
                srCustomerName = customerName;
            }

            // Store settlement context for the live preview.
            window.__srSettlement = {
                invoiceId, invoiceNumber,
                customerId, customerName,
                amount: parse(d.amount), paid: parse(d.paid), balance: parse(d.balance), date: d.date,
            };
            applySettlementMode();
            srSync();
        }

        function applySettlementMode() {
            const st = window.__srSettlement;
            const settlement = !!st && !!document.getElementById('invoice_id').value;

            document.querySelectorAll('.sr-standalone').forEach(el => el.style.display = settlement ? 'none' : '');
            document.querySelectorAll('.sr-settled').forEach(el => el.style.display = settlement ? '' : 'none');
            const pInvRow = document.getElementById('p-inv-row');
            if (pInvRow) pInvRow.style.display = settlement ? '' : 'none';
            const invBtn = document.getElementById('sr-invoice-btn');
            if (invBtn) invBtn.style.display = settlement ? 'none' : '';
            if (settlement) document.getElementById('sr-settlement-card').style.display = '';
        }

        function liveSettlementPreview() {
            const st = window.__srSettlement;
            if (!st || !document.getElementById('invoice_id').value) return;

            document.getElementById('sp-invoice').textContent = st.invoiceNumber;
            document.getElementById('sp-customer').textContent = st.customerName || '—';
            document.getElementById('sp-amount').textContent = SR_CS + fmt(st.amount);
            document.getElementById('sp-paid').textContent = SR_CS + fmt(st.paid);
            document.getElementById('sp-balance').textContent = SR_CS + fmt(Math.max(st.balance, 0));

            let payments = 0;
            document.querySelectorAll('#sr-payments-body .sr-pay-amount').forEach(inp => { payments += parse(inp.value); });

            const applied = Math.min(payments, Math.max(st.balance, 0));
            const over = Math.max(payments - applied, 0);
            const status = applied >= st.balance - 0.001 ? 'Paid' : (applied > 0 ? 'Partially Paid' : st.status || 'Sent');

            document.getElementById('sp-applied').textContent = SR_CS + fmt(applied);
            const overRow = document.getElementById('sp-over-row');
            if (overRow) { overRow.style.display = over > 0 ? '' : 'none'; document.getElementById('sp-over').textContent = SR_CS + fmt(over); }
            const statusEl = document.getElementById('sp-status');
            statusEl.textContent = status;
            statusEl.className = 'v sp-status ' + (status === 'Paid' ? 'is-paid' : (status === 'Partially Paid' ? 'is-partial' : ''));

            const note = document.getElementById('sp-note');
            note.style.display = '';
            note.querySelector('span').textContent = over > 0
                ? 'This receipt receives more than the invoice balance — the excess ' + (window.__overPolicy === 'credit' ? 'will be held as customer credit.' : 'will be capped at the outstanding balance.')
                : 'Payments received will be applied against this invoice\'s outstanding balance.';

            // Rail mirror (only present on edit)
            const pInv = document.getElementById('p-inv');
            if (pInv) pInv.textContent = st.invoiceNumber;
            const pApplied = document.getElementById('p-applied');
            if (pApplied) pApplied.textContent = SR_CS + fmt(applied);
            const pOverRow = document.getElementById('p-over-row');
            if (pOverRow) { pOverRow.style.display = over > 0 ? '' : 'none'; const pOver = document.getElementById('p-over'); if (pOver) pOver.textContent = SR_CS + fmt(over); }
        }

        document.getElementById('sr-invoice-btn').addEventListener('click', () => {
            const custInput = document.querySelector('[name="customer_id"]');
            const custId = custInput ? (custInput.value || '') : '';
            openModal(custId);
        });
        document.getElementById('sr-invoice-close').addEventListener('click', closeModal);
        document.getElementById('sr-invoice-cancel').addEventListener('click', closeModal);
        customerClearBtn.addEventListener('click', e => {
            e.stopPropagation();
            customerFilterLabel.textContent = 'Any customer';
            customerClearBtn.style.display = 'none';
            fetchInvoices();
        });
        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && open) closeModal();
        });
        document.getElementById('sr-clear-invoice').addEventListener('click', () => {
            document.getElementById('invoice_id').value = '';
            document.getElementById('sr-settlement-invoice-label').value = '';
            window.__srSettlement = null;
            applySettlementMode();
            srSync();
            if (window.CB && window.CB.toast) window.CB.toast('info', 'Invoice unlinked', 'This receipt will be recorded as a standalone receipt.');
        });

        // Live preview on payment amount changes.
        document.querySelector('.sr-form')?.addEventListener('input', e => {
            if (e.target.closest('.sr-pay-amount')) { srUpdateTotals(); liveSettlementPreview(); }
        });
        document.querySelector('.sr-form')?.addEventListener('change', e => {
            if (e.target.closest('.sr-pay-amount')) liveSettlementPreview();
        });
        window.addEventListener('srSettlementPreview', liveSettlementPreview);

        // Preselect from query string on first load.
        const preInvoiceId = @json($preselectInvoiceId ?? null);
        if (preInvoiceId) {
            (async () => {
                const params = new URLSearchParams({ q: '' });
                try {
                    const res = await fetch(SR_INVOICE_URL + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    const row = (data.invoices || []).find(i => String(i.id) === String(preInvoiceId));
                    if (row) {
                        window.__srSettlement = {
                            invoiceId: row.id, invoiceNumber: row.invoice_number,
                            customerId: row.customer_id || '', customerName: row.customer_name,
                            amount: row.amount, paid: row.amount_paid, balance: row.balance, date: row.invoice_date,
                            status: row.status,
                        };
                        if (row.customer_id) {
                            const custInput = document.querySelector('[name="customer_id"]');
                            if (custInput) scopedSearchFieldSet(custInput, 'customer', { id: row.customer_id, label: row.customer_name, name: row.customer_name });
                            srCustomerName = row.customer_name;
                        }
                        applySettlementMode();
                        srSync();
                        liveSettlementPreview();
                    }
                } catch (err) {}
            })();
        }
    })();
</script>
    </div>
</div>

<script>
    const SR_PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const SR_ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
    const SR_CS = @json($cs);
    const SR_DEFAULT_INCOME_ACCOUNT_ID = @json((string) $defaultIncomeAccountId);
    const SR_LINES = @json($linesData);
    const SR_PAYMENTS = @json($paymentsData);
    const SR_PAYMENT_METHODS = @json($paymentMethods->map(fn($pm) => ['id' => $pm->id, 'name' => $pm->name, 'type' => $pm->type])->values());
    const SR_MOBILE_PROVIDERS = @json($mobileProviders->pluck('name')->values());
    const SR_INCOME_ACCOUNTS = @json($incomeAccounts->map(fn($a) => ['id' => $a->id, 'label' => $a->code . ' - ' . $a->name])->values());
    let srCustomerName = @json($selectedCustomer?->name ?? '');

    window.__overPolicy = @json(config('sales_receipts.overpayment_policy', 'cap'));

    const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const parse = s => parseFloat(String(s == null ? '' : s).replace(/,/g, '')) || 0;
    const esc = s => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    function fmtDate(v) {
        if (!v) return '—';
        const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return v;
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    let lineIndex = 0;

    function srLineRow(data) {
        const d = data || {};
        const idx = lineIndex++;
        const gross = parse(d.quantity) * parse(d.unit_price);
        const flat = parse(d.discount);
        const pct = d.discount_pct != null
            ? parse(d.discount_pct)
            : (gross > 0 ? Math.round(flat / gross * 10000) / 100 : 0);
        const taxRate = parse(d.tax_rate);
        const incomeAccountId = d.income_account_id || SR_DEFAULT_INCOME_ACCOUNT_ID || '';
        const incomeLabel = (SR_INCOME_ACCOUNTS.find(a => a.id == incomeAccountId) || {}).label || '';

        return `
            <tr class="sr-row">
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" class="q2-ci q2-sku" value="${esc(d.sku || '')}" placeholder="Code" readonly tabindex="-1" aria-label="Item code" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][product_id]`,
                        entity: 'product',
                        searchUrl: SR_PRODUCT_SEARCH_URL,
                        value: d.product_id || '',
                        label: d.label || '',
                        placeholder: 'Search items…',
                    })}
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" class="q2-ci" name="lines[${idx}][description]" value="${esc(d.description || '')}" placeholder="Description" aria-label="Description" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="any" min="0" class="q2-ci q2-numr q2-qty" name="lines[${idx}][quantity]" value="${d.quantity != null ? d.quantity : 1}" aria-label="Quantity" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" class="q2-ci q2-numr q2-price" name="lines[${idx}][unit_price]" value="${d.unit_price != null ? d.unit_price : 0}" aria-label="Unit price" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" max="100" class="q2-ci q2-numr q2-disc-pct" value="${pct}" aria-label="Discount percent" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][income_account_id]`,
                        entity: 'account',
                        searchUrl: SR_ACCOUNT_SEARCH_URL,
                        value: incomeAccountId,
                        label: incomeLabel,
                        placeholder: 'Select account',
                    })}
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <span class="q2-amt--cell q2-line-total">0.00</span>
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <div class="flex gap-1 justify-end">
                        <button type="button" class="q2-ibtn" title="{{ __('Duplicate line') }}" aria-label="{{ __('Duplicate line') }}" onclick="srDuplicateRow(this)">⧉</button>
                        <button type="button" class="q2-ibtn q2-ibtn--del" title="{{ __('Delete line') }}" aria-label="{{ __('Delete line') }}" onclick="srRemoveLine(this)">🗑</button>
                    </div>
                </td>
                <input type="hidden" name="lines[${idx}][tax_rate]" class="sr-tax-rate" value="${taxRate}" />
                <input type="hidden" name="lines[${idx}][discount]" class="sr-flat-discount" value="${flat.toFixed(2)}" />
            </tr>
        `;
    }

    function srRowData(row) {
        const g = sel => (row.querySelector(sel) || { value: '' }).value;
        const num = sel => parse(g(sel));
        const picker = row.querySelector('[name*="[product_id]"]');
        const labelEl = picker && picker.closest('[x-data]')
            ? picker.closest('[x-data]').querySelector('.scoped-search-field input')
            : null;
        const qty = num('.q2-qty');
        const price = num('.q2-price');
        const pct = num('.q2-disc-pct');

        return {
            product_id: g('[name*="[product_id]"]'),
            label: labelEl ? labelEl.value : '',
            sku: g('.q2-sku'),
            description: g('[name*="[description]"]'),
            quantity: qty,
            unit_price: price,
            discount: qty * price > 0 ? Math.round(qty * price * pct) / 100 : 0,
            discount_pct: pct,
            tax_rate: num('.sr-tax-rate'),
            income_account_id: g('[name*="[income_account_id]"]'),
        };
    }

    function srAddLine(data) {
        document.getElementById('sr-lines-body')
            .insertAdjacentHTML('beforeend', srLineRow(data || {}));
        srUpdateTotals();
    }

    function srRemoveLine(btn) {
        const row = btn.closest('tr.sr-row');
        row.remove();
        if (!document.querySelector('#sr-lines-body tr.sr-row')) srAddLine();
        srUpdateTotals();
    }

    function srDuplicateRow(btn) {
        const row = btn.closest('tr.sr-row');
        row.insertAdjacentHTML('afterend', srLineRow(srRowData(row)));
        srUpdateTotals();
    }

    function srUpdateTotals() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;

        document.querySelectorAll('#sr-lines-body tr.sr-row').forEach(row => {
            const qty = parse(row.querySelector('.q2-qty').value);
            const price = parse(row.querySelector('.q2-price').value);
            const pct = parse(row.querySelector('.q2-disc-pct').value);
            const rate = parse(row.querySelector('.sr-tax-rate').value);

            const gross = qty * price;
            const flat = gross > 0 ? Math.round(gross * pct) / 100 : 0;
            row.querySelector('.sr-flat-discount').value = flat.toFixed(2);

            const amount = gross - flat;
            const lineTax = amount * rate / 100;
            row.querySelector('.q2-line-total').textContent = fmt(amount + lineTax);

            subtotal += amount;
            discount += flat;
            tax += lineTax;
        });

        let payments = 0;
        document.querySelectorAll('#sr-payments-body .sr-pay-amount').forEach(inp => {
            payments += parse(inp.value);
        });

        const grand = subtotal + tax;

        document.getElementById('v-sub').textContent = fmt(subtotal);
        const rd = document.getElementById('r-disc');
        rd.style.display = discount > 0 ? '' : 'none';
        document.getElementById('v-disc').textContent = fmt(discount);
        const rt = document.getElementById('r-tax');
        rt.style.display = tax > 0 ? '' : 'none';
        document.getElementById('v-tax').textContent = fmt(tax);
        document.getElementById('v-pay').textContent = fmt(payments);
        document.getElementById('v-gt').textContent = SR_CS + fmt(grand);
    }

    /* ── payments ─────────────────────────────────────────────── */
    let payIndex = 0;

    function srMethodOptions(selected) {
        return SR_PAYMENT_METHODS.map(m =>
            `<option value="${m.id}" data-type="${esc(m.type || '')}" ${String(m.id) === String(selected) ? 'selected' : ''}>${esc(m.name)}</option>`
        ).join('');
    }

    function srExtrasHtml(rowIdx, d) {
        const type = d.type || '';
        if (type === 'cash') {
            return `<div class="q2-field">
                <label class="q2-label" for="sr-cash-${rowIdx}">{{ __('Cash Tendered') }}</label>
                <input id="sr-cash-${rowIdx}" type="number" step="0.01" min="0" class="q2-input" name="payments[${rowIdx}][cash_tendered]" value="${d.cash_tendered != null && d.cash_tendered !== '' ? d.cash_tendered : ''}" placeholder="0.00" aria-label="{{ __('Cash tendered') }}" />
            </div>`;
        }
        if (type === 'mobile_money') {
            const opts = SR_MOBILE_PROVIDERS.map(n =>
                `<option value="${esc(n)}" ${String(d.institution) === String(n) ? 'selected' : ''}>${esc(n)}</option>`
            ).join('');
            return `<div class="q2-field">
                <label class="q2-label" for="sr-provider-${rowIdx}">{{ __('Provider') }}</label>
                <select id="sr-provider-${rowIdx}" name="payments[${rowIdx}][institution]" class="q2-select">${opts}</select>
            </div>`;
        }
        if (type === 'bank_transfer' || type === 'card' || type === 'cheque') {
            return `<div class="q2-field">
                <label class="q2-label" for="sr-ref-${rowIdx}">{{ __('Reference') }}</label>
                <input id="sr-ref-${rowIdx}" type="text" class="q2-input" name="payments[${rowIdx}][reference_number]" value="${esc(d.reference_number || '')}" placeholder="{{ __('Reference / account') }}" />
            </div>`;
        }
        return '';
    }

    function srPaymentRow(data) {
        const d = data || {};
        const idx = payIndex++;
        const extras = srExtrasHtml(idx, d);

        return `
            <div class="q2-paygrid" data-pay-row="${idx}">
                <div class="q2-field">
                    <label class="q2-label" for="sr-method-${idx}">{{ __('Payment Method') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                    <select id="sr-method-${idx}" name="payments[${idx}][payment_method_id]" class="q2-select" onchange="srMethodChange(this)">
                        <option value="">Select method…</option>
                        ${srMethodOptions(d.payment_method_id)}
                    </select>
                </div>
                <div class="q2-field">
                    <label class="q2-label" for="sr-amount-${idx}">{{ __('Amount') }} (${SR_CS}) <span style="color:var(--red-2,#B91C1C)">*</span></label>
                    <input id="sr-amount-${idx}" type="number" step="0.01" min="0" class="q2-input q2-numr sr-pay-amount" name="payments[${idx}][amount]" value="${d.amount != null && d.amount !== '' ? d.amount : ''}" placeholder="0.00" oninput="srUpdateTotals()" aria-label="{{ __('Payment amount') }}" />
                </div>
                <div class="q2-field">
                    <label class="q2-label">&nbsp;</label>
                    <button type="button" class="q2-ibtn q2-ibtn--del" title="{{ __('Remove payment') }}" aria-label="{{ __('Remove payment') }}" onclick="srRemovePayment(this)">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </div>
                ${extras ? `<div class="q2-pay-extras" style="grid-column: 1 / -1">${extras}</div>` : ''}
            </div>
        `;
    }

    function srMethodChange(sel) {
        const row = sel.closest('[data-pay-row]');
        const idx = parseInt(row.dataset.payRow, 10);
        const opt = sel.options[sel.selectedIndex];
        const type = opt ? (opt.dataset.type || '') : '';
        let extrasEl = row.querySelector('.q2-pay-extras');
        const extras = srExtrasHtml(idx, { type });
        if (extrasEl) {
            if (extras) extrasEl.innerHTML = extras; else extrasEl.remove();
        } else if (extras) {
            const div = document.createElement('div');
            div.className = 'q2-pay-extras';
            div.style.gridColumn = '1 / -1';
            div.innerHTML = extras;
            row.appendChild(div);
        }
        srUpdateTotals();
    }

    function srAddPayment(data) {
        document.getElementById('sr-payments-body')
            .insertAdjacentHTML('beforeend', srPaymentRow(data || {}));
        srUpdateTotals();
    }

    function srRemovePayment(btn) {
        const row = btn.closest('[data-pay-row]');
        row.remove();
        if (!document.querySelector('#sr-payments-body [data-pay-row]')) srAddPayment();
        srUpdateTotals();
    }

    /* ── preview sync ─────────────────────────────────────────── */
    function srSync() {
        const pCust = document.getElementById('p-cust');
        if (pCust) pCust.textContent = srCustomerName || '—';
        const pDate = document.getElementById('p-date');
        if (pDate) pDate.textContent = fmtDate(document.getElementById('receipt_date').value);
        const foot = document.getElementById('p-foot');
        if (foot) foot.textContent = document.getElementById('memo').value || '—';
        srUpdateTotals();
        window.dispatchEvent(new CustomEvent('srSettlementPreview'));
    }

    function srCustomerSelected(id, item) {
        if (item) srCustomerName = item.label || item.name || '';
        srSync();
    }

    document.getElementById('sr-add-line').addEventListener('click', () => srAddLine());
    document.getElementById('sr-add-payment').addEventListener('click', () => srAddPayment());

    const srLinesBody = document.getElementById('sr-lines-body');
    srLinesBody.addEventListener('item-selected', e => {
        const row = e.target.closest('tr.sr-row');
        if (!row || !row.querySelector('[name*="[product_id]"]')) return;
        const item = e.detail.item || {};

        if (item.sku != null) {
            const sku = row.querySelector('.q2-sku');
            if (sku) sku.value = item.sku;
        }
        if (item.description) {
            const desc = row.querySelector('[name*="[description]"]');
            if (desc) desc.value = item.description;
        }
        if (item.sales_price != null) {
            const price = row.querySelector('.q2-price');
            if (price) price.value = parse(item.sales_price).toFixed(2);
        }
        if (item.tax_rate != null) {
            const rate = row.querySelector('.sr-tax-rate');
            if (rate) rate.value = item.tax_rate;
        }
        const acctId = item.income_account_id || SR_DEFAULT_INCOME_ACCOUNT_ID || '';
        const acctInput = row.querySelector('[name*="[income_account_id]"]');
        if (acctInput) {
            const acctItem = SR_INCOME_ACCOUNTS.find(a => a.id == acctId);
            scopedSearchFieldSet(acctInput, 'account', {
                id: acctId,
                label: acctItem ? acctItem.label : '',
            });
        }

        srUpdateTotals();
    });

    srLinesBody.addEventListener('input', e => {
        if (e.target.closest('.q2-qty, .q2-price, .q2-disc-pct')) srUpdateTotals();
    });
    srLinesBody.addEventListener('change', e => {
        if (e.target.closest('.q2-qty, .q2-price, .q2-disc-pct')) srUpdateTotals();
    });

    ['receipt_date', 'memo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', srSync);
    });

    document.getElementById('receipt-form').addEventListener('submit', srUpdateTotals);

    (SR_LINES.length ? SR_LINES : [{}]).forEach(d => srAddLine(d));
    (SR_PAYMENTS.length ? SR_PAYMENTS : [{}]).forEach(d => srAddPayment(d));
    srSync();

    /* ── rail mirror: copy canonical li-totals into the rail summary ── */
    (function () {
        const form = document.getElementById('receipt-form');
        const map = { 'v-sub': 'p-sub', 'v-tax': 'p-tax', 'v-pay': 'p-received', 'v-gt': 'p-total' };
        if (!form) return;
        const mirror = () => {
            for (const srcId in map) {
                const src = document.getElementById(srcId);
                const dst = document.getElementById(map[srcId]);
                if (src && dst) dst.textContent = src.textContent;
            }
            const gt = document.getElementById('p-gt');
            const vgt = document.getElementById('v-gt');
            if (gt && vgt) gt.textContent = vgt.textContent;
        };
        form.addEventListener('input', mirror);
        form.addEventListener('change', mirror);
        form.addEventListener('submit', mirror);
        form.addEventListener('item-selected', mirror);
        mirror();
    })();
</script>
