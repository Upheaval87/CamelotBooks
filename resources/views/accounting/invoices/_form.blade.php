@php
    $invoice = $invoice ?? null;
    $isEdit = $isEdit ?? (bool) $invoice;
    $formAction = $formAction ?? ($isEdit ? route('accounting.invoices.update', $invoice) : route('accounting.invoices.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.invoices.show', $invoice) : route('accounting.invoices.index'));
    $title = $title ?? ($isEdit ? 'Edit Invoice' : 'Create Invoice');
    $subtitle = $subtitle ?? 'Bill your customer with a clean, itemised line breakdown.';
    $submitLabel = $submitLabel ?? ($isEdit ? 'Update Invoice' : 'Save Invoice');

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $copyQuote = $copyQuote ?? null;
    $copyQuotes = $copyQuotes ?? collect();
    $preselectCustomer = $preselectCustomer ?? null;

    $selectedCustomerId = old('customer_id', $copyQuote['customer_id'] ?? $preselectCustomer['id'] ?? $invoice?->customer_id ?? '');
    $selectedCustomerLabel = old('customer_name', $copyQuote['customer_name'] ?? $preselectCustomer['name'] ?? ($invoice?->customer?->name ?? ''));
    $selectedCustomer = $customers->firstWhere('id', (int) $selectedCustomerId);

    $lineAccounts = $incomeAccounts->keyBy('id');
    $lineCostCenters = $costCenters->keyBy('id');

    $linesData = [];
    if (old('lines')) {
        foreach (array_values(old('lines')) as $l) {
            $product = $products->firstWhere('id', (int) ($l['product_id'] ?? 0));
            $account = $lineAccounts->get($l['income_account_id'] ?? '');
            $cc = $lineCostCenters->get($l['cost_center_id'] ?? '');
            $linesData[] = [
                'product_id' => $l['product_id'] ?? '',
                'label' => $product?->name ?? '',
                'sku' => $product?->sku ?? '',
                'description' => $l['description'] ?? '',
                'quantity' => (float) ($l['quantity'] ?? 1),
                'unit_price' => (float) ($l['unit_price'] ?? 0),
                'discount' => (float) ($l['discount'] ?? 0),
                'tax_rate' => (float) ($l['tax_rate'] ?? 0),
                'income_account_id' => $l['income_account_id'] ?? '',
                'income_account_label' => $account ? "{$account->code} - {$account->name}" : '',
                'cost_center_id' => $l['cost_center_id'] ?? '',
                'cost_center_label' => $cc ? "{$cc->code} - {$cc->name}" : '',
            ];
        }
    } elseif ($invoice) {
        $linesData = $invoice->lines->map(function ($l) use ($lineAccounts, $lineCostCenters) {
            $account = $lineAccounts->get($l->income_account_id);
            $cc = $lineCostCenters->get($l->cost_center_id);

            return [
                'product_id' => $l->product_id ?? '',
                'label' => $l->product?->name ?? '',
                'sku' => $l->product?->sku ?? '',
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount' => (float) $l->discount,
                'tax_rate' => (float) $l->tax_rate,
                'income_account_id' => $l->income_account_id,
                'income_account_label' => $account ? "{$account->code} - {$account->name}" : '',
                'cost_center_id' => $l->cost_center_id,
                'cost_center_label' => $cc ? "{$cc->code} - {$cc->name}" : '',
            ];
        })->all();
    } elseif ($copyQuote) {
        $linesData = $copyQuote['lines'];
    }
@endphp

<div class="suite">
    {{-- sticky page head --}}
    <div class="sticky-head">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="q2-title" style="font-size:1.375rem">{{ $title }}</h1>
                @if ($isEdit)
                    <span class="mono-chip">{{ $invoice->invoice_number }}</span>
                    <span class="badge b-{{ $invoice->status }}"><span class="bdot"></span>{{ __(ucfirst(str_replace('_', ' ', $invoice->status))) }}</span>
                @endif
            </div>
            <p class="sub">{{ $subtitle }}</p>
        </div>
        <div class="tbtns">
            <button type="button" class="btn btn-ghost" onclick="CopyQuote.open()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Copy from Quote') }}
            </button>
            <a href="{{ $cancelRoute }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" form="invoice-form" class="btn btn-cta">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="invoice-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="shell">
            <div>

                {{-- (a) customer --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2>{{ __('Customer Information') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="customer_id">{{ __('Customer') }} <span class="req">*</span></label>
                            <x-scoped-search-field
                                name="customer_id"
                                entity="customer"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                :value="$selectedCustomerId"
                                :label="$selectedCustomerLabel"
                                placeholder="{{ __('Search customers…') }}"
                                on-select="invCustomerSelected"
                                required
                            />
                            <x-input-error :messages="$errors->get('customer_id')" />
                        </div>
                        <div class="field">
                            <label class="label">{{ __('Contact Person') }}</label>
                            <input type="text" class="input" id="inv-contact" value="{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '' }}" readonly />
                        </div>
                        <div class="field">
                            <label class="label">{{ __('Email') }}</label>
                            <input type="email" class="input" id="inv-email" value="{{ $selectedCustomer?->email ?? '' }}" readonly />
                        </div>
                        <div class="field">
                            <label class="label">{{ __('Phone') }}</label>
                            <input type="text" class="input" id="inv-phone" value="{{ $selectedCustomer?->phone ?? '' }}" readonly />
                        </div>
                        <div class="field">
                            <label class="label">{{ __('Payment Terms') }}</label>
                            <input type="text" class="input" id="inv-terms" value="{{ $selectedCustomer?->payment_terms ?? '' }}" readonly />
                        </div>
                    </div>
                    <p class="hint" style="margin-top:1rem">{{ __('Contact details shown are from the customer record.') }}</p>
                </section>

                {{-- (b) invoice info --}}
                <section class="card card-sec" style="margin-top:16px">
                    <div class="sec-head">
                        <span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2>{{ __('Invoice Information') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field">
                            <label class="label">{{ __('Invoice №') }}</label>
                            <input type="text" class="input" value="{{ $invoice?->invoice_number ?? '' }}" placeholder="{{ __('Auto-assigned on save') }}" readonly />
                        </div>
                        <div class="field">
                            <label for="invoice_date">{{ __('Invoice Date') }} <span class="req">*</span></label>
                            <input id="invoice_date" name="invoice_date" type="date" class="input" value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('invoice_date')" />
                        </div>
                        <div class="field">
                            <label for="due_date">{{ __('Due Date') }} <span class="req">*</span></label>
                            <input id="due_date" name="due_date" type="date" class="input" value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('due_date')" />
                        </div>
                        <div class="field">
                            <label for="reference">{{ __('Reference №') }}</label>
                            <input id="reference" name="reference" type="text" class="input" value="{{ old('reference', $invoice?->reference ?? '') }}" placeholder="{{ __('Optional') }}" />
                            <x-input-error :messages="$errors->get('reference')" />
                        </div>
                    </div>
                    <p class="hint" style="margin-top:1rem">{{ __('Invoice № is auto-assigned on save.') }}</p>
                </section>

                {{-- (c) line items --}}
                <section class="card card-sec relative z-30" style="margin-top:16px">
                    {{-- quick item search overlay -- floats IN FRONT OF the line-items card while typing in a line's Item Name --}}
                    <div id="inv-quick" class="qs-panel" style="display:none" role="listbox" aria-label="{{ __('Quick item search results') }}">
                        <div class="qs-head">
                            <span class="qs-title">{{ __('Quick Item Search') }}</span>
                            <span class="qs-context" id="inv-quick-context"></span>
                        </div>
                        <div class="qs-results" id="inv-quick-results"></div>
                        <div class="qs-state" id="inv-quick-loading" style="display:none">{{ __('Searching…') }}</div>
                        <div class="qs-state" id="inv-quick-none" style="display:none">{{ __('No items match that search.') }}</div>
                    </div>

                    <div class="sec-head">
                        <span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2>{{ __('Line Items') }}</h2>
                        <span class="rule"></span>
                        <button type="button" id="inv-add-line" class="btn btn-ghost btn-sm">＋ {{ __('Add Line') }}</button>
                    </div>

                    <div class="li-wrap">
                        <table id="inv-lines-table" class="table-fixed" style="min-width:960px">
                            <thead>
                                <tr>
                                    <th style="width:8%">{{ __('Item Code') }}</th>
                                    <th style="width:16%">{{ __('Item Name') }}</th>
                                    <th style="width:16%">{{ __('Description') }}</th>
                                    <th style="width:6%" class="num">{{ __('Qty') }}</th>
                                    <th style="width:9%" class="num">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th style="width:7%" class="num">{{ __('Disc %') }}</th>
                                    <th style="width:14%">{{ __('Income Account') }}</th>
                                    <th style="width:12%">{{ __('Cost Center') }}</th>
                                    <th style="width:9%" class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th style="width:3%"></th>
                                </tr>
                            </thead>
                            <tbody id="inv-lines-body"></tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </section>

                {{-- (d) notes --}}
                <section class="card card-sec" style="margin-top:16px">
                    <div class="sec-head">
                        <span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2>{{ __('Notes') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="field" style="margin-top:16px">
                        <label for="memo">{{ __('Description / Memo') }}</label>
                        <textarea id="memo" name="memo" class="input" style="height:auto;min-height:6rem;padding:.75rem .875rem;resize:vertical" placeholder="{{ __('Printed on the invoice…') }}">{{ old('memo', $invoice?->memo ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('memo')" />
                    </div>
                </section>
            </div>

            {{-- rail: summary + quick nav --}}
            <aside class="railsum">
                <div class="card rail-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2>{{ __('Summary') }}</h2>
                    </div>
                    <div style="margin-top:10px">
                        <div class="srow"><span>{{ __('Subtotal') }}</span><span class="v" id="inv-subtotal">0.00</span></div>
                        <div class="srow"><span>{{ __('Tax') }}</span><span class="v" id="inv-tax">0.00</span></div>
                        <div class="gt"><span class="l">{{ __('Total') }}</span><span class="v" id="inv-total">{{ $cs }}0.00</span></div>
                    </div>
                </div>

                <div class="card rail-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 9l9-5.5L21 9M5 9.5V19M9.5 9.5V19M14.5 9.5V19M19 9.5V19M3 19.5h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <h2>{{ __('Quick Nav') }}</h2>
                    </div>
                    <div class="vlist">
                        <a href="{{ route('accounting.customers.create') }}" class="vitem">
                            <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 13v5m2.5-2.5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <span>{{ __('New Customer') }}</span>
                        </a>
                        <a href="{{ route('accounting.customer-payments.create') }}" class="vitem">
                            <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9zm4 4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span>{{ __('Record Payment') }}</span>
                        </a>
                        <a href="{{ route('accounting.invoices.index') }}" class="vitem">
                            <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <span>{{ __('Invoice List') }}</span>
                        </a>
                        <button type="button" class="vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer" onclick="CopyQuote.open()">
                            <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <span>{{ __('Copy from Quote') }}</span>
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>

<x-copy-quote-picker :quotes="$copyQuotes" mode="form" />

<script>
    const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
    const COST_CENTER_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'cost-center']));
    const INV_CS = @json($cs);
    const incomeAccounts = @json($incomeAccounts);
    const costCenters = @json($costCenters);
    const INV_LINES = @json($linesData);

    const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const parse = s => parseFloat(String(s == null ? '' : s).replace(/,/g, '')) || 0;
    const esc = s => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    let lineIndex = 0;

    function invLineRow(data) {
        const d = data || {};
        const idx = lineIndex++;

        return `
            <tr class="inv-row">
                <td>
                    <input type="text" class="bill-ci bill-sku" value="${esc(d.sku || '')}" placeholder="Code" readonly tabindex="-1" aria-label="Item code" />
                </td>
                <td>
                    <input type="hidden" name="lines[${idx}][product_id]" class="inv-product-id" value="${esc(d.product_id || '')}" />
                    <input
                        type="text"
                        class="bill-ci inv-product-search"
                        value="${esc(d.label || '')}"
                        placeholder="Search items…"
                        autocomplete="off"
                        aria-label="Item name"
                    />
                </td>
                <td>
                    <input type="text" name="lines[${idx}][description]" class="bill-ci" value="${esc(d.description || '')}" placeholder="Description" aria-label="Description" readonly />
                </td>
                <td>
                    <input type="number" step="any" min="0" name="lines[${idx}][quantity]" class="bill-ci bill-ci-num bill-qty" value="${d.quantity != null ? d.quantity : 1}" aria-label="Quantity" />
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="lines[${idx}][unit_price]" class="bill-ci bill-ci-num bill-price" value="${d.unit_price != null ? d.unit_price : 0}" aria-label="Unit price" readonly />
                </td>
                <td>
                    <input type="number" step="0.01" min="0" max="100" name="lines[${idx}][discount]" class="bill-ci bill-ci-num bill-disc-pct" value="${d.discount != null ? d.discount : 0}" aria-label="Discount percent" />
                </td>
                <td>
                    <input type="hidden" name="lines[${idx}][tax_rate]" class="inv-tax-rate" value="${d.tax_rate != null ? d.tax_rate : 0}" />
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][income_account_id]`,
                        entity: 'account',
                        searchUrl: ACCOUNT_SEARCH_URL,
                        value: d.income_account_id || '',
                        label: d.income_account_label || '',
                        placeholder: 'Select Account',
                    })}
                </td>
                <td>
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][cost_center_id]`,
                        entity: 'cost-center',
                        searchUrl: COST_CENTER_SEARCH_URL,
                        value: d.cost_center_id || '',
                        label: d.cost_center_label || '',
                        placeholder: 'None',
                    })}
                </td>
                <td>
                    <span class="bill-amt inv-line-total">0.00</span>
                </td>
                <td>
                    <div class="row-act">
                        <button type="button" class="ibtn" title="Duplicate line" aria-label="Duplicate line" onclick="invDuplicateRow(this)">⧉</button>
                        <button type="button" class="ibtn del" title="Delete line" aria-label="Delete line" onclick="invRemoveLine(this)">🗑</button>
                    </div>
                </td>
            </tr>
        `;
    }

    function invAddLine(data) {
        document.getElementById('inv-lines-body')
            .insertAdjacentHTML('beforeend', invLineRow(data || {}));
        invUpdateTotals();
    }

    function invRemoveLine(btn) {
        const row = btn.closest('tr.inv-row');
        row.remove();
        if (!document.querySelector('#inv-lines-body tr.inv-row')) invAddLine();
        invUpdateTotals();
    }

    function invDuplicateRow(btn) {
        const row = btn.closest('tr.inv-row');
        row.insertAdjacentHTML('afterend', invLineRow(invRowData(row)));
        invUpdateTotals();
    }

    function invRowData(row) {
        const g = sel => (row.querySelector(sel) || { value: '' }).value;
        const num = sel => parse(g(sel));

        return {
            product_id: g('.inv-product-id'),
            label: g('.inv-product-search'),
            sku: g('.bill-sku'),
            description: g('[name*="[description]"]'),
            quantity: num('.bill-qty'),
            unit_price: num('.bill-price'),
            discount: num('.bill-disc-pct'),
            tax_rate: num('.inv-tax-rate'),
            income_account_id: g('[name*="[income_account_id]"]'),
            cost_center_id: g('[name*="[cost_center_id]"]'),
        };
    }

    function invUpdateTotals() {
        let subtotal = 0;
        let totalTax = 0;

        document.querySelectorAll('#inv-lines-body tr.inv-row').forEach(row => {
            const qty = parse(row.querySelector('.bill-qty').value);
            const price = parse(row.querySelector('.bill-price').value);
            const pct = parse(row.querySelector('.bill-disc-pct').value);
            const rate = parse(row.querySelector('.inv-tax-rate').value);

            const amount = qty * price * (1 - pct / 100);
            const lineTax = amount * rate / 100;
            row.querySelector('.inv-line-total').textContent = fmt(amount + lineTax);

            subtotal += amount;
            totalTax += lineTax;
        });

        document.getElementById('inv-subtotal').textContent = fmt(subtotal);
        document.getElementById('inv-tax').textContent = fmt(totalTax);
        document.getElementById('inv-total').textContent = INV_CS + fmt(subtotal + totalTax);
    }

    function invCustomerSelected(id, item) {
        if (!item) return;
        if (item.display_name) document.getElementById('inv-contact').value = item.display_name;
        else if (item.label) document.getElementById('inv-contact').value = item.label;
        if (item.email) document.getElementById('inv-email').value = item.email;
        if (item.phone) document.getElementById('inv-phone').value = item.phone;
        if (item.payment_terms) document.getElementById('inv-terms').value = item.payment_terms;
    }

    function invApplyQuote(payload) {
        const custInput = document.querySelector('input[name="customer_id"]');
        if (custInput && payload.customer_id) {
            scopedSearchFieldSet(custInput, 'customer', {
                id: payload.customer_id,
                label: payload.customer_name || '',
            });
        }
        const contact = document.getElementById('inv-contact');
        if (contact && payload.customer_contact != null) contact.value = payload.customer_contact;
        const email = document.getElementById('inv-email');
        if (email && payload.customer_email != null) email.value = payload.customer_email;
        const phone = document.getElementById('inv-phone');
        if (phone && payload.customer_phone != null) phone.value = payload.customer_phone;
        const terms = document.getElementById('inv-terms');
        if (terms && payload.customer_terms != null) terms.value = payload.customer_terms;

        const ref = document.getElementById('reference');
        if (ref && payload.reference != null) ref.value = payload.reference || '';

        const memo = document.getElementById('memo');
        if (memo && payload.memo != null) memo.value = payload.memo || '';

        const tbody = document.getElementById('inv-lines-body');
        if (tbody) tbody.innerHTML = '';

        const lines = payload.lines || [];
        lines.forEach(l => invAddLine(l));
        if (!lines.length) invAddLine();

        invUpdateTotals();
    }

    document.getElementById('inv-add-line').addEventListener('click', () => invAddLine());

    // ---- shared quick item search panel (results appear ABOVE the line-items card) ----
    const invQuick = document.getElementById('inv-quick');
    const invQuickResults = document.getElementById('inv-quick-results');
    const invQuickContext = document.getElementById('inv-quick-context');
    const invQuickLoading = document.getElementById('inv-quick-loading');
    const invQuickNone = document.getElementById('inv-quick-none');

    let invActiveSearchRow = null;
    let invQuickIndex = -1;
    let invQuickItems = [];

    function invFillRow(row, item) {
        if (item.sku != null) { const sku = row.querySelector('.bill-sku'); if (sku) sku.value = item.sku; }
        if (item.description) { const desc = row.querySelector('[name*="[description]"]'); if (desc) desc.value = item.description; }
        if (item.sales_price != null) { const price = row.querySelector('.bill-price'); if (price) price.value = parse(item.sales_price).toFixed(2); }
        if (item.tax_rate != null) { const rate = row.querySelector('.inv-tax-rate'); if (rate) rate.value = item.tax_rate; }

        const pid = row.querySelector('.inv-product-id');
        const search = row.querySelector('.inv-product-search');
        if (pid && item.id !== undefined && item.id !== null) pid.value = item.id;
        if (search && item.label != null) search.value = item.label;

        if (item.income_account_id) {
            const acctInput = row.querySelector('[name*="[income_account_id]"]');
            if (acctInput) {
                const accountItem = incomeAccounts.find(a => a.id == item.income_account_id);
                scopedSearchFieldSet(acctInput, 'account', {
                    id: item.income_account_id,
                    label: accountItem ? accountItem.code + ' - ' + accountItem.name : ''
                });
            }
        }
        invUpdateTotals();
    }

    function invQuickShowState(state) {
        invQuickResults.innerHTML = '';
        invQuickLoading.style.display = state === 'loading' ? '' : 'none';
        invQuickNone.style.display = state === 'none' ? '' : 'none';
        invQuick.style.display = invActiveSearchRow ? '' : 'none';
    }

    function invQuickClose() {
        invActiveSearchRow = null;
        invQuickItems = [];
        invQuickIndex = -1;
        invQuick.style.display = 'none';
        invQuickResults.innerHTML = '';
        invQuickLoading.style.display = 'none';
        invQuickNone.style.display = 'none';
    }

    async function invQuickRun(row, q) {
        q = (q || '').trim();
        if (!q) { invQuickClose(); return; }
        invActiveSearchRow = row;
        const rowIndex = Array.from(document.querySelectorAll('#inv-lines-body tr.inv-row')).indexOf(row) + 1;
        invQuickContext.textContent = 'Row ' + rowIndex;
        invQuickShowState('loading');

        try {
            const r = await fetch(PRODUCT_SEARCH_URL + '?q=' + encodeURIComponent(q));
            if (!r.ok) throw new Error('search failed');
            const items = await r.json();
            if (invActiveSearchRow !== row) return;
            invQuickItems = items;
            invQuickIndex = -1;
            if (!items.length) { invQuickShowState('none'); return; }

            invQuickResults.innerHTML = items.map((it, i) => `
                <div class="qs-option" data-index="${i}" role="option" aria-selected="false">
                    <span class="qs-option-label">${esc(it.label || '')}</span>
                    ${it.sku ? `<span class="qs-option-sku">${esc(it.sku)}</span>` : ''}
                </div>`).join('');
            invQuickLoading.style.display = 'none';
            invQuick.style.display = '';
        } catch (e) {
            invQuickShowState('none');
        }
    }

    function invQuickPick(row, item) {
        invFillRow(row, item);
        invQuickClose();
        const qty = row.querySelector('.bill-qty');
        if (qty) qty.focus();
    }

    function invQuickMove(dir) {
        if (!invQuickItems.length) return;
        invQuickIndex += dir;
        if (invQuickIndex < 0) invQuickIndex = invQuickItems.length - 1;
        if (invQuickIndex >= invQuickItems.length) invQuickIndex = 0;
        Array.from(invQuickResults.children).forEach((el, i) => {
            el.classList.toggle('is-highlighted', i === invQuickIndex);
            el.setAttribute('aria-selected', i === invQuickIndex ? 'true' : 'false');
        });
    }

    const invLinesBody = document.getElementById('inv-lines-body');
    invLinesBody.addEventListener('focusin', e => {
        const s = e.target.closest('.inv-product-search');
        if (s && s.value.trim()) { const row = s.closest('tr.inv-row'); invQuickRun(row, s.value); }
    });
    invLinesBody.addEventListener('input', e => {
        const s = e.target.closest('.inv-product-search');
        if (s) { const row = s.closest('tr.inv-row'); invQuickRun(row, s.value); return; }
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) invUpdateTotals();
    });
    invLinesBody.addEventListener('change', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) invUpdateTotals();
    });
    invLinesBody.addEventListener('keydown', e => {
        if (!e.target.closest('.inv-product-search')) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); invQuickMove(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); invQuickMove(-1); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            const row = e.target.closest('tr.inv-row');
            if (!row || !invQuickItems.length) return;
            const item = invQuickIndex >= 0 ? invQuickItems[invQuickIndex] : invQuickItems[0];
            invQuickPick(row, item);
        }
        else if (e.key === 'Escape') { invQuickClose(); }
    });
    invQuickResults.addEventListener('click', e => {
        const opt = e.target.closest('.qs-option');
        if (!opt || !invActiveSearchRow) return;
        const idx = parseInt(opt.getAttribute('data-index'), 10);
        if (invQuickItems[idx]) invQuickPick(invActiveSearchRow, invQuickItems[idx]);
    });
    document.addEventListener('click', e => {
        if (invActiveSearchRow && !e.target.closest('#inv-quick') && !e.target.closest('.inv-product-search')) invQuickClose();
    });

    document.getElementById('invoice-form').addEventListener('submit', invUpdateTotals);

    (INV_LINES.length ? INV_LINES : [{}]).forEach(d => invAddLine(d));
</script>
