@php
    $requisition = $requisition ?? null;
    $isEdit = $isEdit ?? (bool) $requisition;
    $formAction = $formAction ?? ($isEdit ? route('accounting.purchase-requisitions.update', $requisition) : route('accounting.purchase-requisitions.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.purchase-requisitions.show', $requisition) : route('accounting.purchase-requisitions.index'));
    $title = $title ?? ($isEdit ? __('Edit Requisition') : __('Create Requisition'));
    $submitLabel = $submitLabel ?? ($isEdit ? __('Save Changes') : __('Save'));

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $defaultExpenseAccountId = $defaultExpenseAccountId ?? ($expenseAccounts->first()?->id ?? '');

    $requesterName = $requisition?->requestedBy?->name ?? $requisition?->createdBy?->name ?? auth()->user()?->name ?? '';

    $selectedBranchId = old('branch_id', $requisition?->branch_id ?? $branches->first()?->id ?? '');
    $selectedCostCenterId = old('cost_center_id', $requisition?->cost_center_id ?? '');
    $selectedCostCenterLabel = $selectedCostCenterId ? ($costCenters->firstWhere('id', (int) $selectedCostCenterId)?->name ?? '') : '';

    if ($isEdit) {
        $prDate = $requisition?->date?->format('M d, Y') ?? '—';
        $neededDate = $requisition?->required_by?->format('M d, Y') ?? __('no due date');
        $subtitle = "{$requesterName} · " . __('on') . " {$prDate} · " . __('needed by') . " {$neededDate}";
    } else {
        $subtitle = $subtitle ?? __('Request goods or services for internal approval.');
    }

    $linesData = [];
    if (old('lines')) {
        foreach (array_values(old('lines')) as $l) {
            $product = $products->firstWhere('id', (int) ($l['product_id'] ?? 0));
            $linesData[] = [
                'product_id' => $l['product_id'] ?? '',
                'label' => $product?->name ?? '',
                'sku' => $product?->sku ?? '',
                'description' => $l['description'] ?? '',
                'quantity' => (float) ($l['quantity'] ?? 1),
                'estimated_unit_cost' => (float) ($l['estimated_unit_cost'] ?? 0),
                'tax_rate' => (float) ($product?->tax_rate ?? 0),
                'expense_account_id' => $l['expense_account_id'] ?? $defaultExpenseAccountId,
                'expense_account_label' => $accounts->firstWhere('id', (int) ($l['expense_account_id'] ?? 0))?->name ?? '',
                'cost_center_id' => $l['cost_center_id'] ?? '',
            ];
        }
    } elseif ($requisition) {
        $linesData = $requisition->lines->map(function ($l) use ($accounts) {
            return [
                'product_id' => $l->product_id ?? '',
                'label' => $l->product?->name ?? '',
                'sku' => $l->product?->sku ?? '',
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'estimated_unit_cost' => (float) ($l->estimated_unit_cost ?? 0),
                'tax_rate' => (float) ($l->product?->tax_rate ?? 0),
                'expense_account_id' => $l->expense_account_id,
                'expense_account_label' => $l->expense_account_id ? ($accounts->firstWhere('id', (int) $l->expense_account_id)?->name ?? '') : '',
                'cost_center_id' => $l->cost_center_id ?? '',
            ];
        })->all();
    }

    $costCenterOptions = $costCenters->map(fn ($cc) => ['id' => $cc->id, 'name' => $cc->code ? "[{$cc->code}] {$cc->name}" : $cc->name]);
@endphp

<div class="pr-suite wrap">

    {{-- Sticky page head --}}
    <div class="sticky-head">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="page-title">{{ $title }}</h1>
                @if ($isEdit)
                    <span class="mono-chip">{{ $requisition->requisition_number }}</span>
                    <span class="badge b-{{ $requisition->status === 'submitted' ? 'pend' : $requisition->status }}"><span class="bdot"></span>{{ __(ucfirst($requisition->statusLabel())) }}</span>
                @endif
            </div>
            <div class="sub">{{ $subtitle }}</div>
        </div>
        <div class="cluster">
            <a href="{{ $cancelRoute }}" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</a>
            @if ($isEdit)
                @if($requisition->created_by && (int) $requisition->created_by !== (int) auth()->id())
                    <button type="submit" form="requisition-delete-form" class="btn btn-danger-o btn-sm">{{ __('Delete') }}</button>
                @endif
                <div class="seg">
                    <button type="submit" name="action" value="save" form="requisition-form" class="btn btn-sec">{{ $submitLabel }}</button>
                    <button type="submit" name="action" value="submit_for_approval" form="requisition-form" class="btn btn-cta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('Submit for Approval') }}
                    </button>
                </div>
            @else
                <div class="seg">
                    <button type="submit" name="action" value="save_draft" form="requisition-form" class="btn btn-ghost">{{ __('Save Draft') }}</button>
                    <button type="submit" name="action" value="save" form="requisition-form" class="btn btn-sec">{{ $submitLabel }}</button>
                    <button type="submit" name="action" value="submit_for_approval" form="requisition-form" class="btn btn-cta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('Submit for Approval') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="requisition-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        {{-- Live summary --}}
        <div class="sumbar" aria-label="Requisition summary">
            <div class="cell"><div class="l">{{ __('Subtotal') }}</div><div class="v" id="pr-subtotal">0.00</div><div class="n"><span id="pr-lines-count">0</span> {{ __('lines') }}</div></div>
            <div class="cell"><div class="l">{{ __('Est. Tax') }}</div><div class="v" id="pr-tax">0.00</div><div class="n">—</div></div>
            <div class="cell"><div class="l">{{ __('Budget Check') }}</div><div class="v" id="pr-budget">—</div><div class="n">{{ __('on submit') }}</div></div>
            <div class="cell hero"><div class="l">{{ __('Grand Total') }}</div><div class="v" id="pr-grand">{{ $cs }}0.00</div></div>
        </div>

        <section class="card" style="margin-top:20px">

            {{-- Requisition Details --}}
            <div class="card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <h2>{{ __('Requisition Details') }}</h2><span class="rule"></span>
                </div>
                <div class="g4">
                    <div class="field">
                        <label>{{ __('Requested By') }}</label>
                        <input class="input h44" value="{{ $requesterName }}" readonly />
                    </div>
                    <div class="field">
                        <label for="date">{{ __('Date') }} <span style="color:var(--red-2,#b91c1c)">*</span></label>
                        <input id="date" name="date" type="date" class="input h44" value="{{ old('date', $requisition?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-1" />
                    </div>
                    <div class="field">
                        <label for="department">{{ __('Department') }}</label>
                        <select id="department" name="department" class="input h44">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach($departments as $dep)
                                <option value="{{ $dep }}" @selected(old('department', $requisition?->department) == $dep)>{{ $dep }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department')" class="mt-1" />
                    </div>
                    <div class="field">
                        <label for="branch_id">{{ __('Branch (Optional)') }}</label>
                        <x-scoped-search-field
                            name="branch_id"
                            entity="branch"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                            :value="$selectedBranchId"
                            :label="$selectedBranchId ? ($branches->firstWhere('id', (int) $selectedBranchId)?->name ?? '') : ''"
                            placeholder="{{ __('None') }}"
                        />
                        <x-input-error :messages="$errors->get('branch_id')" class="mt-1" />
                    </div>
                    <div class="field">
                        <label for="cost_center_id">{{ __('Cost Centre') }}</label>
                        <x-scoped-search-field
                            name="cost_center_id"
                            entity="cost-center"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                            :value="$selectedCostCenterId"
                            :label="$selectedCostCenterLabel"
                            placeholder="{{ __('None') }}"
                        />
                        <x-input-error :messages="$errors->get('cost_center_id')" class="mt-1" />
                    </div>
                    <div class="field">
                        <label for="required_by">{{ __('Needed By') }}</label>
                        <input id="required_by" name="required_by" type="date" class="input h44" value="{{ old('required_by', $requisition?->required_by?->format('Y-m-d')) }}" />
                        <x-input-error :messages="$errors->get('required_by')" class="mt-1" />
                    </div>
                    <div class="field">
                        <label for="priority">{{ __('Priority') }}</label>
                        <select id="priority" name="priority" class="input h44">
                            <option value="normal" @selected(old('priority', $requisition?->priority ?? 'normal') === 'normal')>{{ __('Normal') }}</option>
                            <option value="urgent" @selected(old('priority', $requisition?->priority) === 'urgent')>{{ __('Urgent') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('priority')" class="mt-1" />
                    </div>
                    <div class="field sp2">
                        <label for="supplier">{{ __('Suggested Supplier (optional)') }}</label>
                        <input id="supplier" name="supplier" type="text" class="input h44" value="{{ old('supplier', $requisition?->supplier) }}" placeholder="{{ __('Search suppliers…') }}" />
                        <x-input-error :messages="$errors->get('supplier')" class="mt-1" />
                    </div>
                    <div class="field">
                        <label for="reference">{{ __('Reference') }}</label>
                        <input id="reference" name="reference" type="text" class="input h44" value="{{ old('reference', $requisition?->reference) }}" placeholder="{{ __('Optional') }}" />
                        <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <h2>{{ __('Line Items') }}</h2><span class="rule"></span>
                    <button type="button" class="btn btn-ghost btn-sm" id="pr-add-line" style="margin-left:12px">＋ {{ __('Add Line') }}</button>
                </div>
                <div class="li-wrap">
                    <table class="pr-lines-table">
                        <thead>
                            <tr>
                                <th style="width:9%">{{ __('Code') }}</th>
                                <th style="width:18%">{{ __('Item') }}</th>
                                <th style="width:21%">{{ __('Description') }}</th>
                                <th class="num" style="width:7%">{{ __('Qty') }}</th>
                                <th class="num" style="width:11%">{{ __('Est. Unit Price') }}</th>
                                <th style="width:14%">{{ __('Expense Acct') }}</th>
                                <th style="width:10%">{{ __('Cost Centre') }}</th>
                                <th class="num" style="width:11%">{{ __('Amount') }} ({{ $cs }})</th>
                                <th style="width:7%"></th>
                            </tr>
                        </thead>
                        <tbody id="pr-lines-body">
                        </tbody>
                    </table>
                </div>
                <div class="li-totals"><div class="box">
                    <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v" id="pr-lt-sub">0.00</span></div>
                    <div class="trow"><span>{{ __('Est. Tax') }}</span><span class="v" id="pr-lt-tax">0.00</span></div>
                    <div class="trow total"><span>{{ __('Grand Total') }}</span><span class="v" id="pr-lt-grand">{{ $cs }}0.00</span></div>
                </div></div>
                <x-input-error :messages="$errors->get('lines')" class="mt-2" />
            </div>

            {{-- Notes --}}
            <div class="card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 3h8l4 4v14H4V3h4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 11h8M8 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <h2>{{ __('Notes') }}</h2><span class="rule"></span>
                </div>
                <div class="g4" style="grid-template-columns:1fr 1fr">
                    <div class="field sp2">
                        <label for="memo">{{ __('Justification / Notes') }}</label>
                        <textarea id="memo" name="memo" class="input" rows="4" placeholder="{{ __('Why is this purchase needed?') }}">{{ old('memo', $requisition?->memo) }}</textarea>
                        <x-input-error :messages="$errors->get('memo')" class="mt-1" />
                    </div>
                </div>
            </div>
        </section>
    </form>

    @if ($isEdit && $requisition->created_by && (int) $requisition->created_by !== (int) auth()->id())
    <form id="requisition-delete-form" method="POST" action="{{ route('accounting.purchase-requisitions.destroy', $requisition) }}" onsubmit="return fbConfirmSubmit(event, 'Delete this draft requisition? This cannot be undone.', { type: 'danger' })">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>

<script>
    const PR_CS = @json($cs);
    const PR_DEFAULT_EXPENSE_ACCOUNT_ID = @json((string) $defaultExpenseAccountId);
    const PR_LINES = @json($linesData);
    const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
    const PR_COST_CENTERS = @json($costCenterOptions);
    const PR_ACCOUNT_LABELS = @json($accounts->mapWithKeys(fn ($a) => [(string) $a->id => $a->name]));

    const prFmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const prParse = s => parseFloat(String(s == null ? '' : s).replace(/,/g, '')) || 0;
    const prEsc = s => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    let prLineIndex = 0;

    function prCostCenterOptions(selected) {
        return '<option value="">—</option>' + PR_COST_CENTERS.map(cc =>
            `<option value="${cc.id}" ${String(cc.id) === String(selected) ? 'selected' : ''}>${prEsc(cc.name)}</option>`
        ).join('');
    }

    function prLineRow(data) {
        const d = data || {};
        const idx = prLineIndex++;
        const expAcctId = d.expense_account_id || PR_DEFAULT_EXPENSE_ACCOUNT_ID || '';

        return `
            <tr class="pr-row">
                <td>
                    <input type="text" class="ci pr-sku" value="${prEsc(d.sku || '')}" placeholder="Code" readonly tabindex="-1" aria-label="Item code" />
                </td>
                <td>
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][product_id]`,
                        entity: 'product',
                        searchUrl: PRODUCT_SEARCH_URL,
                        value: d.product_id || '',
                        label: d.label || '',
                        placeholder: 'Search items…',
                    })}
                </td>
                <td>
                    <input type="text" class="ci" name="lines[${idx}][description]" value="${prEsc(d.description || '')}" placeholder="Description" aria-label="Description" required />
                </td>
                <td class="num">
                    <input type="number" step="any" min="0.01" class="ci pr-qty" name="lines[${idx}][quantity]" value="${d.quantity != null ? d.quantity : 1}" aria-label="Quantity" required />
                </td>
                <td class="num">
                    <input type="number" step="0.01" min="0" class="ci pr-price" name="lines[${idx}][estimated_unit_cost]" value="${d.estimated_unit_cost != null ? d.estimated_unit_cost : 0}" aria-label="Estimated unit cost" />
                </td>
                <td>
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][expense_account_id]`,
                        entity: 'account',
                        searchUrl: ACCOUNT_SEARCH_URL,
                        value: expAcctId,
                        label: d.expense_account_label || '',
                        placeholder: 'None',
                    })}
                </td>
                <td>
                    <select class="ci pr-cost-center" name="lines[${idx}][cost_center_id]" aria-label="Cost centre">
                        ${prCostCenterOptions(d.cost_center_id || '')}
                    </select>
                </td>
                <td class="num">
                    <span class="amt pr-line-total">0.00</span>
                </td>
                <td>
                    <div class="row-act">
                        <button type="button" class="ibtn" title="Duplicate line" aria-label="Duplicate line" onclick="prDuplicateRow(this)">⧉</button>
                        <button type="button" class="ibtn del" title="Delete line" aria-label="Delete line" onclick="prRemoveLine(this)">🗑</button>
                    </div>
                </td>
                <input type="hidden" class="pr-tax-rate" value="${prParse(d.tax_rate)}" />
            </tr>
        `;
    }

    function prRowData(row) {
        const g = sel => (row.querySelector(sel) || { value: '' }).value;
        const num = sel => prParse(g(sel));
        const productPicker = row.querySelector('[name*="[product_id]"]');
        const productLabelEl = productPicker && productPicker.closest('[x-data]')
            ? productPicker.closest('[x-data]').querySelector('.scoped-search-field input')
            : null;
        const acctPicker = row.querySelector('[name*="[expense_account_id]"]');
        const acctLabelEl = acctPicker && acctPicker.closest('[x-data]')
            ? acctPicker.closest('[x-data]').querySelector('.scoped-search-field input')
            : null;

        return {
            product_id: g('[name*="[product_id]"]'),
            label: productLabelEl ? productLabelEl.value : '',
            sku: g('.pr-sku'),
            description: g('[name*="[description]"]'),
            quantity: num('.pr-qty'),
            estimated_unit_cost: num('.pr-price'),
            tax_rate: num('.pr-tax-rate'),
            expense_account_id: g('[name*="[expense_account_id]"]'),
            expense_account_label: acctLabelEl ? acctLabelEl.value : '',
            cost_center_id: g('.pr-cost-center'),
        };
    }

    function prAddLine(data) {
        document.getElementById('pr-lines-body')
            .insertAdjacentHTML('beforeend', prLineRow(data || {}));
        prUpdateTotals();
    }

    function prRemoveLine(btn) {
        const row = btn.closest('tr.pr-row');
        row.remove();
        if (!document.querySelector('#pr-lines-body tr.pr-row')) prAddLine();
        prUpdateTotals();
    }

    function prDuplicateRow(btn) {
        const row = btn.closest('tr.pr-row');
        row.insertAdjacentHTML('afterend', prLineRow(prRowData(row)));
        prUpdateTotals();
    }

    function prUpdateTotals() {
        let subtotal = 0;
        let tax = 0;
        let count = 0;

        document.querySelectorAll('#pr-lines-body tr.pr-row').forEach(row => {
            const qty = prParse(row.querySelector('.pr-qty').value);
            const price = prParse(row.querySelector('.pr-price').value);
            const rate = prParse(row.querySelector('.pr-tax-rate').value);
            const amount = qty * price;
            row.querySelector('.pr-line-total').textContent = prFmt(amount);
            subtotal += amount;
            tax += amount * rate / 100;
            count++;
        });

        const grand = subtotal + tax;

        document.getElementById('pr-subtotal').textContent = prFmt(subtotal);
        document.getElementById('pr-tax').textContent = prFmt(tax);
        document.getElementById('pr-grand').textContent = PR_CS + prFmt(grand);
        document.getElementById('pr-lines-count').textContent = count;
        document.getElementById('pr-lt-sub').textContent = prFmt(subtotal);
        document.getElementById('pr-lt-tax').textContent = prFmt(tax);
        document.getElementById('pr-lt-grand').textContent = PR_CS + prFmt(grand);
    }

    document.getElementById('pr-lines-body').addEventListener('item-selected', function (e) {
        const row = e.target.closest('tr.pr-row');
        if (!row) return;
        const item = e.detail.item;
        if (item.description) {
            const desc = row.querySelector('[name*="[description]"]');
            if (desc && !desc.value) desc.value = item.description;
        }
        if (item.sku) {
            const sku = row.querySelector('.pr-sku');
            if (sku) sku.value = item.sku;
        }
        if (item.purchase_price) {
            const cost = row.querySelector('.pr-price');
            if (cost && (!cost.value || prParse(cost.value) === 0)) {
                cost.value = parseFloat(item.purchase_price).toFixed(2);
            }
        }
        const taxRate = row.querySelector('.pr-tax-rate');
        if (taxRate) taxRate.value = prParse(item.tax_rate);
        if (item.expense_account_id) {
            const acctPicker = row.querySelector('[name*="[expense_account_id]"]');
            if (acctPicker) {
                scopedSearchFieldSet(acctPicker, 'account', { id: item.expense_account_id, label: PR_ACCOUNT_LABELS[item.expense_account_id] || '' });
            }
        }
        prUpdateTotals();
    });

    document.getElementById('pr-add-line').addEventListener('click', () => prAddLine());

    PR_LINES.forEach(line => prAddLine(line));
    if (!document.querySelector('#pr-lines-body tr.pr-row')) prAddLine();

    document.getElementById('requisition-form').addEventListener('submit', prUpdateTotals);
</script>
