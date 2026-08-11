@php
    $isEdit = $isEdit ?? false;
    $order = $order ?? null;
    $requisition = $requisition ?? null;
    $selectedVendorId = $selectedVendorId ?? '';
    $existingLines = $existingLines ?? [];
    $productsJson = $products->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'sales_price' => $p->sales_price,
            'purchase_price' => $p->purchase_price,
            'type' => $p->type,
            'description' => $p->description,
            'tracked_as_inventory' => $p->tracked_as_inventory,
            'income_account_id' => $p->income_account_id,
            'expense_account_id' => $p->expense_account_id ?? null,
            'tax_rate' => $p->tax_rate,
        ];
    })->values();
@endphp

<div class="suite pb-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

        <div class="sticky-head">
            <div>
                <h1>{{ $title }}</h1>
                <div class="sub">{{ $subtitle }}</div>
            </div>
            <div class="tbtns">
                <a href="{{ $cancelRoute }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" form="po-form" class="btn btn-cta">{{ $submitLabel }}</button>
            </div>
        </div>

        <div class="shell">
            <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                <form method="POST" action="{{ $formAction }}" id="po-form">
                    @csrf
                    @if($formMethod === 'PUT') @method('PUT') @endif

                    {{-- Order details --}}
                    <section class="card card-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4M3 7l9 4 9-4M5 10v10m4-7h6m-6 4h6m4-7v10"/></svg></span>
                            <h2>{{ __('Order Details') }}</h2>
                            <span class="rule"></span>
                        </div>
                        <div class="g4">
                            <div class="field sp2">
                                <label for="vendor_id">{{ __('Vendor') }} <span class="req">*</span></label>
                                <x-scoped-search-field
                                    name="vendor_id"
                                    entity="vendor"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                                    :value="old('vendor_id', $isEdit ? ($order->vendor_id ?? '') : $selectedVendorId)"
                                    :label="old('vendor_name', $isEdit ? ($order->vendor->name ?? '') : ($vendors->firstWhere('id', (int) $selectedVendorId)?->name ?? ''))"
                                    placeholder="{{ __('Search vendors...') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                            </div>
                            <div class="field">
                                <label for="date">{{ __('Date') }} <span class="req">*</span></label>
                                <input id="date" name="date" type="date" class="input" value="{{ old('date', $isEdit ? $order->date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required />
                                <x-input-error :messages="$errors->get('date')" class="mt-2" />
                            </div>
                            <div class="field">
                                <label for="expected_delivery_date">{{ __('Expected Delivery') }}</label>
                                <input id="expected_delivery_date" name="expected_delivery_date" type="date" class="input" value="{{ old('expected_delivery_date', $isEdit ? $order->expected_delivery_date?->format('Y-m-d') : '') }}" />
                                <x-input-error :messages="$errors->get('expected_delivery_date')" class="mt-2" />
                            </div>
                            <div class="field">
                                <label for="branch_id">{{ __('Branch') }}</label>
                                <x-scoped-search-field
                                    name="branch_id"
                                    entity="branch"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                    :value="old('branch_id', $isEdit ? $order->branch_id : '')"
                                    :label="old('branch_id', $isEdit ? $order->branch_id : '') ? ($branches->firstWhere('id', (int) old('branch_id', $isEdit ? $order->branch_id : ''))?->name ?? '') : ''"
                                    placeholder="{{ __('None') }}"
                                />
                            </div>
                            <div class="field">
                                <label for="cost_center_id">{{ __('Cost Center') }}</label>
                                <x-scoped-search-field
                                    name="cost_center_id"
                                    entity="cost-center"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                    :value="old('cost_center_id', $isEdit ? $order->cost_center_id : '')"
                                    :label="old('cost_center_id', $isEdit ? $order->cost_center_id : '') ? ($costCenters->firstWhere('id', (int) old('cost_center_id', $isEdit ? $order->cost_center_id : ''))?->name ?? '') : ''"
                                    placeholder="{{ __('None') }}"
                                />
                            </div>
                            <div class="field sp4">
                                <label for="memo">{{ __('Description') }}</label>
                                <input id="memo" name="memo" type="text" class="input" value="{{ old('memo', $isEdit ? $order->memo : '') }}" placeholder="{{ __('Optional memo') }}" />
                                <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    {{-- Line items --}}
                    <section class="card card-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                            <h2>{{ __('Line Items') }}</h2>
                            <span class="rule"></span>
                            <button type="button" id="add-line" class="btn btn-sec btn-sm">+ {{ __('Add Line') }}</button>
                        </div>
                        <div class="li-wrap">
                            <table id="lines-table">
                                <thead><tr>
                                    <th style="width:22%">{{ __('Product') }}</th>
                                    <th style="width:20%">{{ __('Description') }}</th>
                                    <th class="num" style="width:8%">{{ __('Qty') }}</th>
                                    <th class="num" style="width:12%">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th style="width:20%">{{ __('Expense Account') }}</th>
                                    <th class="num" style="width:12%">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th style="width:6%"></th>
                                </tr></thead>
                                <tbody id="lines-body"></tbody>
                            </table>
                        </div>
                        <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                        <div class="li-totals">
                            <div class="box">
                                <div class="trow total"><span>{{ __('Total') }}:</span><span class="v" id="grand-total">0.00</span></div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>

            {{-- rail --}}
            <aside class="railsum">
                <section class="card">
                    <div class="rail-sec">
                        <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                        <div class="vlist">
                            <a href="{{ route('accounting.purchase-orders.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 12h6m-6 4h6M9 8h6M7 4h10a2 2 0 0 1 2 2v16H5V6a2 2 0 0 1 2-2z"/></svg></span>{{ __('Purchase Orders List') }}</a>
                            <a href="{{ route('accounting.vendors.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm14 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>{{ __('Vendors') }}</a>
                            <a href="{{ route('accounting.vendors.create') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v16m8-8H4"/></svg></span>{{ __('New Vendor') }}</a>
                            <a href="{{ route('accounting.bills.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2z"/></svg></span>{{ __('Bills') }}</a>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>

@php $productsJson = $productsJson->all(); @endphp

<script>
    const products = @json($productsJson);
    const expenseAccounts = @json($accounts);
    @if($isEdit)
        const existingLines = @json($existingLines);
    @endif
    @if($requisition)
        const requisition = @json($requisition);
    @endif
    const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
    let lineIndex = 0;

    function expenseAccountLabel(id) {
        const a = expenseAccounts.find(x => x.id == id);
        return a ? a.code + ' - ' + a.name : '';
    }

    function updateTotals() {
        let total = 0;
        document.querySelectorAll('#lines-body tr').forEach(row => {
            const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
            const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
            const amt = qty * price;
            row.querySelector('.line-total').textContent = amt.toFixed(2);
            total += amt;
        });
        const gt = document.getElementById('grand-total');
        if (gt) gt.textContent = total.toFixed(2);
    }

    function addLine(data) {
        var tbody = document.getElementById('lines-body');
        var idx = lineIndex++;
        var selectedId = data ? String(data.product_id || '') : '';
        var selectedName = data && data.product_id
            ? (products.find(function(p) { return p.id == data.product_id; }) || {}).name || ''
            : '';
        var picker = scopedSearchFieldHtml({
            name: 'lines[' + idx + '][product_id]',
            entity: 'product',
            searchUrl: PRODUCT_SEARCH_URL,
            value: selectedId,
            label: selectedName,
            placeholder: 'Search products...',
        });
        var expenseAccountId = data ? (data.expense_account_id || '') : '';
        var expenseAccountField = scopedSearchFieldHtml({
            name: 'lines[' + idx + '][expense_account_id]',
            entity: 'account',
            searchUrl: ACCOUNT_SEARCH_URL,
            value: expenseAccountId,
            label: expenseAccountId ? expenseAccountLabel(expenseAccountId) : '',
            placeholder: 'Select',
            required: true,
        });
        var tr = document.createElement('tr');
        tr.setAttribute('data-line-idx', idx);
        tr.innerHTML =
            '<td style="min-width:220px">' + picker + '</td>' +
            '<td><input type="text" name="lines[' + idx + '][description]" value="' + (data ? (data.description || '') : '') + '" readonly class="input" style="background:rgba(238,244,244,.7)" /></td>' +
            '<td><input type="number" name="lines[' + idx + '][quantity]" value="' + (data ? data.quantity : 1) + '" min="0.01" step="any" class="input" style="text-align:right" onchange="updateTotals()" oninput="updateTotals()" required /></td>' +
            '<td><input type="number" name="lines[' + idx + '][unit_price]" value="' + (data ? (data.unit_price || 0) : 0) + '" min="0" step="0.01" readonly class="input" style="text-align:right;background:rgba(238,244,244,.7)" onchange="updateTotals()" oninput="updateTotals()" required /></td>' +
            '<td style="min-width:200px">' + expenseAccountField + '</td>' +
            '<td class="numr line-total" style="font-weight:600">0.00</td>' +
            '<td class="num"><button type="button" onclick="removeLine(this)" class="ibtn del" title="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg></button></td>';
        tbody.appendChild(tr);
        updateTotals();
    }

    function removeLine(btn) {
        btn.closest('tr').remove();
        updateTotals();
    }

    @if($isEdit)
        existingLines.forEach(line => addLine(line));
        if (existingLines.length === 0) addLine();
    @elseif($requisition)
        requisition.lines.forEach(line => {
            addLine({
                product_id: line.product_id,
                description: line.description,
                quantity: line.quantity,
                unit_price: line.estimated_unit_cost || 0,
                expense_account_id: line.expense_account_id
            });
        });
    @else
        addLine();
    @endif

    document.getElementById('add-line').addEventListener('click', () => addLine());
    document.getElementById('lines-body').addEventListener('item-selected', function(e) {
        var row = e.target.closest('tr');
        if (!row) return;
        var item = e.detail.item;
        if (item.description) {
            var descInput = row.querySelector('[name*="[description]"]');
            if (descInput) descInput.value = item.description;
        }
        if (item.purchase_price) {
            var priceInput = row.querySelector('[name*="[unit_price]"]');
            if (priceInput) priceInput.value = parseFloat(item.purchase_price).toFixed(2);
        }
        if (item.tax_rate !== undefined && item.tax_rate !== null) {
            var taxInput = row.querySelector('[name*="[tax_rate]"]');
            if (taxInput) taxInput.value = parseFloat(item.tax_rate).toFixed(2);
        }
        if (item.expense_account_id) {
            var acctInput = row.querySelector('[name*="[expense_account_id]"]');
            if (acctInput) {
                var accountItem = expenseAccounts.find(function(a) { return a.id == item.expense_account_id; });
                scopedSearchFieldSet(acctInput, 'account', {
                    id: item.expense_account_id,
                    label: accountItem ? accountItem.code + ' - ' + accountItem.name : ''
                });
            }
        }
        updateTotals();
    });
</script>
