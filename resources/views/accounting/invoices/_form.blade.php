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

    $secIc = 'w-7 h-7 rounded-[9px] grid place-items-center text-white bg-[#128F8E] shadow-[inset_0_1px_0_rgba(255,255,255,.18),0_3px_8px_-3px_rgba(10,80,80,.4)]';
    $secHead = 'flex items-center gap-3';
    $btnTertiary = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] border border-transparent bg-transparent text-gray-600 transition-all duration-150 hover:bg-white/75 hover:text-[#0B2A2D] hover:-translate-y-px active:translate-y-0';
    $btnGhost = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] border border-shell bg-white/85 text-gray-700 shadow-sm transition-all duration-150 hover:bg-[rgba(17,69,75,.06)] hover:border-navy-700/25 hover:text-[#0B2A2D] hover:-translate-y-px active:translate-y-0';
    $btnPrimary = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] text-white border border-white/25 bg-gradient-to-b from-gold-500 to-gold-600 shadow-new transition-all duration-150 hover:-translate-y-px active:translate-y-0';
    $selectWrap = 'relative';
    $selectChevron = 'pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500';
@endphp

<div>
    {{-- sticky page head --}}
    <div class="form-page-head flex items-start justify-between gap-4 flex-wrap pb-4 mb-6 border-b border-line">
        <div>
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ $title }}</h1>
            <p class="mt-1 text-[13.5px] text-gray-500">{{ $subtitle }}</p>
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <button type="button" class="{{ $btnTertiary }}" onclick="CopyQuote.open()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Copy from Quote') }}
            </button>
            <a href="{{ $cancelRoute }}" class="{{ $btnGhost }}">{{ __('Cancel') }}</a>
            <button type="submit" form="invoice-form" class="{{ $btnPrimary }}">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="invoice-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="grid gap-6 items-start lg:grid-cols-[1fr_340px]">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- customer --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Customer Information') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-5 xl:grid-cols-4">
                        <div class="col-span-2">
                            <label for="customer_id" class="input-label">{{ __('Customer') }} <span class="text-red-600">*</span></label>
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
                            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Contact Person') }}</label>
                            <input type="text" class="input" id="inv-contact" value="{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '' }}" readonly />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Email') }}</label>
                            <input type="email" class="input" id="inv-email" value="{{ $selectedCustomer?->email ?? '' }}" readonly />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Phone') }}</label>
                            <input type="text" class="input" id="inv-phone" value="{{ $selectedCustomer?->phone ?? '' }}" readonly />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Payment Terms') }}</label>
                            <input type="text" class="input" id="inv-terms" value="{{ $selectedCustomer?->payment_terms ?? '' }}" readonly />
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-400">Contact details shown are from the customer record.</p>
                </section>

                {{-- invoice info --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Invoice Information') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-5 xl:grid-cols-4">
                        <div>
                            <label class="input-label">{{ __('Invoice №') }}</label>
                            <input type="text" class="input" value="{{ $invoice?->invoice_number ?? '' }}" placeholder="{{ __('Auto-assigned on save') }}" readonly />
                        </div>
                        <div>
                            <label for="invoice_date" class="input-label">{{ __('Invoice Date') }} <span class="text-red-600">*</span></label>
                            <input id="invoice_date" name="invoice_date" type="date" class="input" value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('invoice_date')" class="mt-2" />
                        </div>
                        <div>
                            <label for="due_date" class="input-label">{{ __('Due Date') }} <span class="text-red-600">*</span></label>
                            <input id="due_date" name="due_date" type="date" class="input" value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                        </div>
                        <div>
                            <label for="reference" class="input-label">{{ __('Reference №') }}</label>
                            <input id="reference" name="reference" type="text" class="input" value="{{ old('reference', $invoice?->reference ?? '') }}" placeholder="{{ __('Optional') }}" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-400">Invoice № is auto-assigned on save.</p>
                </section>

                {{-- line items --}}
                <section class="card relative z-30 rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Line Items') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                        <button type="button" id="inv-add-line" class="{{ $btnGhost }}" style="height:34px;padding:0 13px;font-size:12.5px;border-radius:10px;margin-left:12px;">＋ {{ __('Add Line') }}</button>
                    </div>

                    <div class="mt-4 border border-shell rounded-[14px] overflow-visible round-thead-clip bg-[#fbfcfe]">
                        <table id="inv-lines-table" class="x-wset-create w-full border-collapse text-[13px] table-fixed">
                            <thead>
                                <tr>
                                    <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Item Code') }}</th>
                                    <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Item Name') }}</th>
                                    <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Description') }}</th>
                                    <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Qty') }}</th>
                                    <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Disc %') }}</th>
                                    <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Income Account') }}</th>
                                    <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Cost Center') }}</th>
                                    <th class="py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th class="py-[11px] px-2.5 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead"></th>
                                </tr>
                            </thead>
                            <tbody id="inv-lines-body"></tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </section>

                {{-- notes --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Notes') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="mt-5">
                        <label for="memo" class="input-label">{{ __('Description / Memo') }}</label>
                        <textarea id="memo" name="memo" class="input" placeholder="Printed on the invoice…">{{ old('memo', $invoice?->memo ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                    </div>
                </section>
            </div>

            {{-- right rail --}}
            <aside class="x-rail">
                <div class="x-rail-card">
                    <div class="x-rail-title">{{ __('Summary') }}</div>
                    <div class="x-totals">
                        <div class="x-totals-row"><span>{{ __('Subtotal') }}</span><span id="inv-subtotal">0.00</span></div>
                        <div class="x-totals-row"><span>{{ __('Tax') }}</span><span id="inv-tax">0.00</span></div>
                        <div class="x-strip x-strip--gt"><span>{{ __('Total') }}</span><span id="inv-total">{{ $cs }}0.00</span></div>
                    </div>
                </div>

                <nav class="x-rail-nav">
                    <a href="{{ route('accounting.customers.create') }}" class="x-rail-link">{{ __('New Customer') }}</a>
                    <a href="{{ route('accounting.customer-payments.create') }}" class="x-rail-link">{{ __('Record Payment') }}</a>
                    <a href="{{ route('accounting.invoices.index') }}" class="x-rail-link">{{ __('Invoice List') }}</a>
                    <button type="button" class="x-rail-link" onclick="CopyQuote.open()">{{ __('Copy from Quote') }}</button>
                </nav>
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
