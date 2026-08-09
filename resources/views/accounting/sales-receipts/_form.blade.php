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

<div class="q2">
    {{-- sticky page head --}}
    <div class="q2-head q2-head--sticky">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="q2-title" style="font-size:1.375rem">{{ $title }}</h1>
                @if ($isEdit)
                    <span class="q2-mono" style="padding:.25rem .625rem;border:1px solid var(--line,#E2ECEC);border-radius:.5rem;background:#fff">{{ $salesReceipt->receipt_number }}</span>
                    <span class="q2-badge q2-badge--draft"><span class="q2-dot"></span>{{ __('Draft') }}</span>
                @endif
            </div>
            <p class="q2-sub">{{ $subtitle }}</p>
        </div>
        <div class="q2-head-actions">
            <a href="{{ $cancelRoute }}" class="q2-btn q2-btn--ghost">{{ __('Cancel') }}</a>
            @if($isEdit)
                @if($salesReceipt->created_by && (int) $salesReceipt->created_by !== (int) auth()->id())
                    <button type="submit" form="receipt-delete-form" class="q2-btn q2-btn--danger">{{ __('Delete') }}</button>
                @endif
                <div class="q2-seg">
                    <button type="submit" name="action" value="save" form="receipt-form" class="q2-btn q2-btn--sec">{{ $submitLabel }}</button>
                    <button type="submit" name="action" value="save_and_post" form="receipt-form" class="q2-btn q2-btn--cta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('Save & Post') }}
                    </button>
                </div>
            @else
                <button type="submit" name="action" value="save_and_new" form="receipt-form" class="q2-btn q2-btn--ghost">{{ __('Save & New') }}</button>
                <div class="q2-seg">
                    <button type="submit" name="action" value="save_draft" form="receipt-form" class="q2-btn q2-btn--ghost">{{ __('Save Draft') }}</button>
                    <button type="submit" name="action" value="save" form="receipt-form" class="q2-btn q2-btn--sec">{{ $submitLabel }}</button>
                    <button type="submit" name="action" value="save_and_post" form="receipt-form" class="q2-btn q2-btn--cta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('Save & Post') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="receipt-form" class="q2-form" novalidate data-customer-name="{{ $selectedCustomer?->name ?? '' }}">
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="q2-shell q2-shell--form">
            <div class="q2-main">

                {{-- (a) customer --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Customer Information') }}</h2>
                    </div>
                    <div class="q2-g4 mt-5">
                        <div class="q2-field" style="grid-column: span 2">
                            <label for="customer_id" class="q2-label">{{ __('Customer') }}</label>
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
                        </div>
                        <div class="q2-field" style="grid-column: span 2">
                            <label for="receipt_date" class="q2-label">{{ __('Receipt Date') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <input id="receipt_date" name="receipt_date" type="date" class="q2-input" value="{{ old('receipt_date', $salesReceipt?->receipt_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('receipt_date')" />
                        </div>
                    </div>
                </section>

                {{-- (b) receipt information --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm0 9h12M6 12l3 3M6 12l3-3M9 7h6M6 18h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Receipt Information') }}</h2>
                    </div>
                    <div class="q2-g4 mt-5">
                        <div class="q2-field">
                            <label for="receipt_number_ro" class="q2-label">{{ __('Receipt №') }}</label>
                            <input id="receipt_number_ro" type="text" class="q2-input" value="{{ $salesReceipt?->receipt_number ?? __('Auto-assigned on save') }}" readonly tabindex="-1" />
                        </div>
                        <div class="q2-field">
                            <label for="reference" class="q2-label">{{ __('Reference') }}</label>
                            <input id="reference" name="reference" type="text" class="q2-input" value="{{ old('reference', $salesReceipt?->reference ?? '') }}" placeholder="{{ __('Optional reference') }}" />
                        </div>
                        <div class="q2-field" style="grid-column: span 2">
                            <label for="branch_id" class="q2-label">{{ __('Branch') }}</label>
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
                        <div class="q2-field" style="grid-column: span 4">
                            <label for="memo" class="q2-label">{{ __('Description') }}</label>
                            <textarea id="memo" name="memo" rows="2" class="q2-input" placeholder="{{ __('Optional memo') }}">{{ old('memo', $salesReceipt?->memo ?? '') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- (c) line items --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Line Items') }}</h2>
                        <button type="button" id="sr-add-line" class="q2-btn q2-btn--soft q2-btn--sm" style="margin-left:auto">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('Add Line') }}
                        </button>
                    </div>
                    <div class="q2-tbl-wrap" style="margin-top:1rem">
                        <table class="q2-tbl" id="sr-lines-table" style="min-width:940px">
                            <thead>
                                <tr>
                                    <th style="width:7rem">{{ __('Code') }}</th>
                                    <th style="min-width:11rem">{{ __('Product') }}</th>
                                    <th style="min-width:10rem">{{ __('Description') }}</th>
                                    <th class="q2-numr" style="width:5.5rem">{{ __('Qty') }}</th>
                                    <th class="q2-numr" style="width:6.5rem">{{ __('Price') }} ({{ $cs }})</th>
                                    <th class="q2-numr" style="width:5.5rem">{{ __('Disc %') }}</th>
                                    <th style="min-width:11rem">{{ __('Income Account') }}</th>
                                    <th class="q2-numr" style="width:6.5rem">{{ __('Line Total') }}</th>
                                    <th style="width:3.5rem"></th>
                                </tr>
                            </thead>
                            <tbody id="sr-lines-body"></tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                    <div class="q2-note-info">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ __('Totals recalculate automatically. Posting requires total payments to match the receipt total.') }}</span>
                    </div>
                </section>

                {{-- (d) payments --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/><path d="M15 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Payments') }}</h2>
                        <button type="button" id="sr-add-payment" class="q2-btn q2-btn--soft q2-btn--sm" style="margin-left:auto">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('Add Payment') }}
                        </button>
                    </div>
                    <div class="q2-paygrid" style="margin-top:1.25rem">
                        <span class="q2-label">{{ __('Payment Method') }}</span>
                        <span class="q2-label">{{ __('Amount') }} ({{ $cs }})</span>
                        <span></span>
                    </div>
                    <div id="sr-payments-body" style="display:flex;flex-direction:column;gap:0.5rem;margin-top:0.25rem"></div>
                    <x-input-error :messages="$errors->get('payments')" class="mt-2" />
                </section>
            </div>

            {{-- rail: summary + quick nav --}}
            <aside class="q2-rail">
                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ $isEdit ? __('Breakdown') : __('Summary') }}</div>
                    <div class="q2-railsum">
                        <div class="q2-srow"><span>{{ __('Customer') }}</span><span class="q2-sval" id="p-cust">{{ $selectedCustomer?->name ?? __('Walk-in') }}</span></div>
                        <div class="q2-srow"><span>{{ __('Date') }}</span><span class="q2-sval" id="p-date">{{ $salesReceipt?->receipt_date?->format('M d, Y') ?? now()->format('M d, Y') }}</span></div>
                        <div style="height:10px"></div>
                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval" id="v-sub">0.00</span></div>
                        <div class="q2-srow" id="r-disc" style="display:none"><span>{{ __('Discount') }}</span><span class="q2-sval" id="v-disc">0.00</span></div>
                        <div class="q2-srow" id="r-tax" style="display:none"><span>{{ __('Tax') }}</span><span class="q2-sval" id="v-tax">0.00</span></div>
                        <div class="q2-srow"><span>{{ __('Payments') }}</span><span class="q2-sval" id="v-pay">0.00</span></div>
                        <div class="q2-srow gt"><span>{{ __('Grand Total') }}</span><span class="q2-sval" id="v-gt">{{ $cs }}0.00</span></div>
                        <p class="q2-rail-memo" id="p-foot" hidden></p>
                    </div>
                </div>

                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ __('Quick Nav') }}</div>
                    @if ($isEdit)
                        <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" rel="noopener" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Print / PDF') }}</span>
                        </a>
                        @if ($salesReceipt?->customer?->email)
                            <form method="POST" action="{{ route('accounting.sales-receipts.email', $salesReceipt) }}" class="q2-vitem">
                                @csrf
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm18 3-10 7L2 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>{{ __('Email') }}</span>
                            </form>
                        @endif
                        <a href="{{ $cancelRoute }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Back') }}</span>
                        </a>
                    @else
                        <a href="{{ route('accounting.sales-receipts.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>{{ __('Receipts List') }}</span>
                        </a>
                        <a href="{{ route('accounting.customers.create') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 13v5m2.5-2.5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>{{ __('New Customer') }}</span>
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </form>

    @if($isEdit && $salesReceipt->created_by && (int) $salesReceipt->created_by !== (int) auth()->id())
    <form id="receipt-delete-form" method="POST" action="{{ route('accounting.sales-receipts.destroy', $salesReceipt) }}" onsubmit="return fbConfirmSubmit(event, 'Delete this draft receipt? This cannot be undone.', { type: 'danger' })">
        @csrf
        @method('DELETE')
    </form>
    @endif
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
        document.getElementById('p-cust').textContent = srCustomerName || '—';
        document.getElementById('p-date').textContent = fmtDate(document.getElementById('receipt_date').value);
        document.getElementById('p-foot').textContent = document.getElementById('memo').value || '—';
        srUpdateTotals();
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
</script>
