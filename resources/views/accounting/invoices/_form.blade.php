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

<div class="q2">
    {{-- §2/§3 sticky page head --}}
    <div class="q2-head q2-head--sticky">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="q2-title" style="font-size:1.375rem">{{ $title }}</h1>
                @if ($isEdit)
                    <span class="q2-mono" style="padding:.25rem .625rem;border:1px solid var(--line,#E2ECEC);border-radius:.5rem;background:#fff">{{ $invoice->invoice_number }}</span>
                    <span class="q2-badge q2-badge--{{ $invoice->status }}"><span class="q2-dot"></span>{{ __(ucfirst(str_replace('_', ' ', $invoice->status))) }}</span>
                @endif
            </div>
            <p class="q2-sub">{{ $subtitle }}</p>
        </div>
        <div class="q2-head-actions">
            <button type="button" class="q2-btn q2-btn--ghost" onclick="CopyQuote.open()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Copy from Quote') }}
            </button>
            <a href="{{ $cancelRoute }}" class="q2-btn q2-btn--ghost">{{ __('Cancel') }}</a>
            <button type="submit" form="invoice-form" class="q2-btn q2-btn--cta">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="invoice-form" class="q2-form" novalidate>
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
                            <label for="customer_id" class="q2-label">{{ __('Customer') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
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
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Contact Person') }}</label>
                            <input type="text" class="q2-input" id="inv-contact" value="{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Email') }}</label>
                            <input type="email" class="q2-input" id="inv-email" value="{{ $selectedCustomer?->email ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Phone') }}</label>
                            <input type="text" class="q2-input" id="inv-phone" value="{{ $selectedCustomer?->phone ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Payment Terms') }}</label>
                            <input type="text" class="q2-input" id="inv-terms" value="{{ $selectedCustomer?->payment_terms ?? '' }}" readonly />
                        </div>
                    </div>
                    <p class="q2-hint mt-4">{{ __('Contact details shown are from the customer record.') }}</p>
                </section>

                {{-- (b) invoice info --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Invoice Information') }}</h2>
                    </div>
                    <div class="q2-g4 mt-5">
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Invoice №') }}</label>
                            <input type="text" class="q2-input" value="{{ $invoice?->invoice_number ?? '' }}" placeholder="{{ __('Auto-assigned on save') }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label for="invoice_date" class="q2-label">{{ __('Invoice Date') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <input id="invoice_date" name="invoice_date" type="date" class="q2-input" value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('invoice_date')" />
                        </div>
                        <div class="q2-field">
                            <label for="due_date" class="q2-label">{{ __('Due Date') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <input id="due_date" name="due_date" type="date" class="q2-input" value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('due_date')" />
                        </div>
                        <div class="q2-field">
                            <label for="reference" class="q2-label">{{ __('Reference №') }}</label>
                            <input id="reference" name="reference" type="text" class="q2-input" value="{{ old('reference', $invoice?->reference ?? '') }}" placeholder="{{ __('Optional') }}" />
                            <x-input-error :messages="$errors->get('reference')" />
                        </div>
                    </div>
                    <p class="q2-hint mt-4">{{ __('Invoice № is auto-assigned on save.') }}</p>
                </section>

                {{-- (c) line items --}}
                <section class="q2-sec relative z-30">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Line Items') }}</h2>
                        <button type="button" id="inv-add-line" class="q2-btn q2-btn--ghost q2-btn--sm" style="margin-left:auto">＋ {{ __('Add Line') }}</button>
                    </div>

                    <div class="mt-4 border border-shell round-thead-clip bg-[#fbfcfe]">
                        <table id="inv-lines-table" class="q2-tbl w-full text-[13px] table-fixed">
                            <thead>
                                <tr>
                                    <th style="width:8%">{{ __('Item Code') }}</th>
                                    <th style="width:16%">{{ __('Item Name') }}</th>
                                    <th style="width:16%">{{ __('Description') }}</th>
                                    <th style="width:6%" class="q2-right">{{ __('Qty') }}</th>
                                    <th style="width:9%" class="q2-right">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th style="width:7%" class="q2-right">{{ __('Disc %') }}</th>
                                    <th style="width:14%">{{ __('Income Account') }}</th>
                                    <th style="width:12%">{{ __('Cost Center') }}</th>
                                    <th style="width:9%" class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th style="width:3%"></th>
                                </tr>
                            </thead>
                            <tbody id="inv-lines-body"></tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </section>

                {{-- (d) notes --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Notes') }}</h2>
                    </div>
                    <div class="q2-g2 mt-5">
                        <div class="q2-field">
                            <label for="memo" class="q2-label">{{ __('Description / Memo') }}</label>
                            <textarea id="memo" name="memo" class="q2-input" style="height:auto;min-height:6rem;padding:.75rem .875rem;resize:vertical" placeholder="{{ __('Printed on the invoice…') }}">{{ old('memo', $invoice?->memo ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('memo')" />
                        </div>
                    </div>
                </section>
            </div>

            {{-- §2/§3 rail: summary + quick nav --}}
            <aside class="q2-rail">
                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ __('Summary') }}</div>
                    <div class="q2-railsum">
                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval" id="inv-subtotal">0.00</span></div>
                        <div class="q2-srow"><span>{{ __('Tax') }}</span><span class="q2-sval" id="inv-tax">0.00</span></div>
                        <div class="q2-srow gt"><span>{{ __('Total') }}</span><span class="q2-sval" id="inv-total">{{ $cs }}0.00</span></div>
                    </div>
                </div>

                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ __('Quick Nav') }}</div>
                    <a href="{{ route('accounting.customers.create') }}" class="q2-vitem">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 13v5m2.5-2.5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <span>{{ __('New Customer') }}</span>
                    </a>
                    <a href="{{ route('accounting.customer-payments.create') }}" class="q2-vitem">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-9zm4 4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ __('Record Payment') }}</span>
                    </a>
                    <a href="{{ route('accounting.invoices.index') }}" class="q2-vitem">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <span>{{ __('Invoice List') }}</span>
                    </a>
                    <button type="button" class="q2-vitem" style="width:100%;text-align:left;background:none;border:0;cursor:pointer" onclick="CopyQuote.open()">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ __('Copy from Quote') }}</span>
                    </button>
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
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" class="bill-ci bill-sku" value="${esc(d.sku || '')}" placeholder="Code" readonly tabindex="-1" aria-label="Item code" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][product_id]`,
                        entity: 'product',
                        searchUrl: PRODUCT_SEARCH_URL,
                        value: d.product_id || '',
                        label: d.label || '',
                        placeholder: 'Search items…',
                    })}
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" name="lines[${idx}][description]" class="bill-ci" value="${esc(d.description || '')}" placeholder="Description" aria-label="Description" readonly />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="any" min="0" name="lines[${idx}][quantity]" class="bill-ci bill-ci-num bill-qty" value="${d.quantity != null ? d.quantity : 1}" aria-label="Quantity" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" name="lines[${idx}][unit_price]" class="bill-ci bill-ci-num bill-price" value="${d.unit_price != null ? d.unit_price : 0}" aria-label="Unit price" readonly />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" max="100" name="lines[${idx}][discount]" class="bill-ci bill-ci-num bill-disc-pct" value="${d.discount != null ? d.discount : 0}" aria-label="Discount percent" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
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
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][cost_center_id]`,
                        entity: 'cost-center',
                        searchUrl: COST_CENTER_SEARCH_URL,
                        value: d.cost_center_id || '',
                        label: d.cost_center_label || '',
                        placeholder: 'None',
                    })}
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <span class="bill-amt inv-line-total">0.00</span>
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <div class="flex gap-1 justify-end">
                        <button type="button" class="bill-ibtn" title="Duplicate line" aria-label="Duplicate line" onclick="invDuplicateRow(this)">⧉</button>
                        <button type="button" class="bill-ibtn bill-ibtn--del" title="Delete line" aria-label="Delete line" onclick="invRemoveLine(this)">🗑</button>
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
            product_id: g('[name*="[product_id]"]'),
            label: (row.querySelector('.scoped-search-field input') || { value: '' }).value || '',
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

    const invLinesBody = document.getElementById('inv-lines-body');
    invLinesBody.addEventListener('item-selected', e => {
        const row = e.target.closest('tr.inv-row');
        if (!row || !row.querySelector('[name*="[product_id]"]')) return;
        const item = e.detail.item || {};

        if (item.sku != null) {
            const sku = row.querySelector('.bill-sku');
            if (sku) sku.value = item.sku;
        }
        if (item.description) {
            const desc = row.querySelector('[name*="[description]"]');
            if (desc) desc.value = item.description;
        }
        if (item.sales_price != null) {
            const price = row.querySelector('.bill-price');
            if (price) price.value = parse(item.sales_price).toFixed(2);
        }
        if (item.tax_rate != null) {
            const rate = row.querySelector('.inv-tax-rate');
            if (rate) rate.value = item.tax_rate;
        }
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
    });

    invLinesBody.addEventListener('input', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) invUpdateTotals();
    });
    invLinesBody.addEventListener('change', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) invUpdateTotals();
    });

    document.getElementById('invoice-form').addEventListener('submit', invUpdateTotals);

    (INV_LINES.length ? INV_LINES : [{}]).forEach(d => invAddLine(d));
</script>
