@props([
    'isEdit' => false,
    'expense' => null,
    'formAction' => '',
    'formMethod' => 'POST',
    'cancelRoute' => '',
    'title' => 'Record Expense',
    'subtitle' => 'Capture a business expense against an account, budget and cost centre.',
    'submitLabel' => 'Save & Submit',
    'cs' => '$',
    'budget' => null,
])

@php
    $existingFiles = $expense?->attachments ?? collect();
    $systemCurrency = \App\Models\Company::find(session('current_company_id'))?->base_currency ?? 'USD';
@endphp

<div class="ex-suite wrap">
    <div class="sticky-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="sub" style="margin:2px 0 0">{{ $subtitle }}</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <a href="{{ $cancelRoute }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            @if($isEdit && $expense && $expense->created_by !== auth()->id())
                <form id="expense-delete-form" method="POST" action="{{ route('accounting.expenses.destroy', $expense) }}" class="inline"
                      onsubmit="return fbConfirmSubmit(event, '{{ __('Delete this expense?') }}', { type: 'danger' })">
                    @csrf
                    @method('DELETE')
                </form>
                <button type="submit" form="expense-delete-form" class="btn btn-danger-o">{{ __('Delete') }}</button>
            @endif
            <div class="seg">
                <button type="submit" form="expense-form" name="action" value="save_draft" class="btn btn-ghost btn-sm">{{ __('Save Draft') }}</button>
                <button type="submit" form="expense-form" name="action" value="submit" class="btn btn-cta btn-sm">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="expense-form" class="form" enctype="multipart/form-data">
        @csrf
        @if($formMethod === 'PUT')
            @method('PUT')
        @endif

        <div class="sumbar sticky" aria-label="{{ __('Amounts') }}">
            <div class="cell">
                <div class="l">{{ __('Subtotal') }}</div>
                <div class="v" id="subtotal">0.00</div>
            </div>
            <div class="cell">
                <div class="l">{{ __('VAT incl.') }}</div>
                <div class="v" id="total-tax">0.00</div>
            </div>
            <div class="cell">
                <div class="l">{{ __('Discount') }}</div>
                <div class="v" id="total-discount">0.00</div>
            </div>
            <div class="cell hero">
                <div class="l">{{ __('Total') }}</div>
                <div class="v" id="grand-total">{{ $cs }}0.00</div>
            </div>
        </div>

        <section class="card" style="margin-top:16px">
            <div class="sec-head" style="padding:20px 24px 0">
                <span class="sec-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4zm0 4h14" stroke="currentColor" stroke-width="1.8"/><path d="M8 12h3M8 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <div class="t">{{ __('Expense Information') }}</div>
                    <div class="d">{{ __('Core details for this expense.') }}</div>
                </div>
            </div>
            <div class="g4" style="padding:0 24px 20px">
                <div class="field">
                    <label class="label">{{ __('Expense #') }}</label>
                    <input class="input h44" value="{{ $isEdit ? $expense->expense_number : __('Auto-assigned on save') }}" disabled />
                </div>
                <div class="field">
                    <label class="label" for="expense_date">{{ __('Expense Date') }} <span class="req">*</span></label>
                    <input id="expense_date" name="expense_date" type="date" class="input h44"
                           value="{{ old('expense_date', $isEdit ? $expense->expense_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required />
                    @error('expense_date')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="label" for="category_id">{{ __('Category') }}</label>
                    <select id="category_id" name="category_id" class="input h44">
                        <option value="">{{ __('Uncategorised') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $isEdit ? $expense->category_id : '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="label">{{ __('Vendor') }}</label>
                    <x-scoped-search-field
                        name="vendor_id"
                        entity="vendor"
                        search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                        :value="old('vendor_id', $isEdit ? $expense->vendor_id : ($selectedVendorId ?? ''))"
                        :label="old('vendor_name', ($isEdit ? ($expense->vendor?->name ?? '') : ($vendors->firstWhere('id', (int) ($selectedVendorId ?? ''))?->name ?? '')))"
                        placeholder="{{ __('Search vendors...') }}"
                    />
                    @error('vendor_id')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="label">{{ __('Cost Centre') }}</label>
                    <x-scoped-search-field
                        name="cost_center_id"
                        entity="cost-center"
                        search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                        :value="old('cost_center_id', $isEdit ? $expense->cost_center_id : '')"
                        :label="old('cost_center_name', ($costCenters->firstWhere('id', (int) ($isEdit ? $expense->cost_center_id : old('cost_center_id')))?->name ?? ''))"
                        placeholder="{{ __('None') }}"
                    />
                    @error('cost_center_id')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="label" for="department">{{ __('Department') }}</label>
                    <input id="department" name="department" type="text" class="input h44"
                           value="{{ old('department', $isEdit ? $expense->department : '') }}" placeholder="{{ __('Optional') }}" />
                    @error('department')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="label" for="reference">{{ __('Reference') }}</label>
                    <input id="reference" name="reference" type="text" class="input h44"
                           value="{{ old('reference', $isEdit ? $expense->reference : '') }}" placeholder="{{ __('Optional reference') }}" />
                    @error('reference')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="label" for="currency">{{ __('Currency') }}</label>
                    <select id="currency" name="currency" class="input h44">
                        @forelse($currencies as $curOption)
                            <option value="{{ $curOption->code }}" {{ old('currency', $isEdit ? ($expense->currency ?? $systemCurrency) : $systemCurrency) === $curOption->code ? 'selected' : '' }}>{{ $curOption->code }} - {{ $curOption->name }}</option>
                        @empty
                            <option value="{{ $systemCurrency }}">{{ $systemCurrency }}</option>
                        @endforelse
                    </select>
                    @error('currency')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="rule" style="height:1px;background:var(--line,#e2ecec);margin:0 24px"></div>
            <div class="field" style="padding:16px 24px 20px">
                <label class="label" for="memo">{{ __('Description') }}</label>
                <input id="memo" name="memo" type="text" class="input"
                       value="{{ old('memo', $isEdit ? $expense->memo : '') }}" placeholder="{{ __('What was this expense for?') }}" />
                @error('memo')<span class="err">{{ $message }}</span>@enderror
            </div>
        </section>

        <section class="card" style="margin-top:16px">
            <div class="sec-head" style="padding:20px 24px 0">
                <span class="sec-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M7 7v3m4-3v3m4-3v3m4-3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 11h12l-1.5 9h-9L6 11z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <div class="t">{{ __('Accounting Allocation') }}</div>
                    <div class="d">{{ __('Break the expense into accounts. Qty × Unit Price minus discount, plus tax.') }}</div>
                </div>
                <button type="button" id="add-line" class="btn btn-ghost btn-sm" style="margin-left:auto">{{ __('+ Add Line') }}</button>
            </div>

            <div class="li-wrap" style="margin:16px 24px 0">
                <table id="lines-table">
                    <thead>
                        <tr>
                            <th style="width:17%">{{ __('Expense Account') }}</th>
                            <th style="width:20%">{{ __('Description') }}</th>
                            <th style="width:10%">{{ __('Department') }}</th>
                            <th class="num" style="width:7%">{{ __('Qty') }}</th>
                            <th class="num" style="width:9%">{{ __('Unit Price') }} ({{ $cs }})</th>
                            <th class="num" style="width:7%">{{ __('Disc %') }}</th>
                            <th class="num" style="width:7%">{{ __('Tax %') }}</th>
                            <th style="width:11%">{{ __('Cost Centre') }}</th>
                            <th class="num" style="width:9%">{{ __('Line Total') }}</th>
                            <th style="width:34px"></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8">{{ __('Total') }}</td>
                            <td class="num alloc-total" id="alloc-total">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @error('lines')<span class="err" style="display:block;margin:8px 24px 0">{{ $message }}</span>@enderror
        </section>

        <section class="card" style="margin-top:16px">
            <div class="sec-head" style="padding:20px 24px 0">
                <span class="sec-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.8"/></svg>
                </span>
                <div>
                    <div class="t">{{ __('Payment Information') }}</div>
                    <div class="d">{{ __('Where this expense draws from when settled.') }}</div>
                </div>
                <span class="fmt" style="margin-left:auto">{{ __('expense ≠ payment') }}</span>
            </div>
            <div class="g2" style="padding:16px 24px 20px">
                <div class="field">
                    <label class="label">{{ __('Paid From Account') }}</label>
                    <x-scoped-search-field
                        name="bank_account_id"
                        entity="bank-account"
                        search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                        :value="old('bank_account_id', $isEdit ? $expense->bank_account_id : '')"
                        :label="old('bank_account_name', ($bankAccounts->firstWhere('id', (int) ($isEdit ? $expense->bank_account_id : old('bank_account_id')))?->name ?? ''))"
                        placeholder="{{ __('Search bank accounts...') }}"
                    />
                    @error('bank_account_id')<span class="err">{{ $message }}</span>@enderror
                    <div class="hint">{{ __('Recording this expense does not create a payment. Pay it separately when it is due.') }}</div>
                </div>
            </div>
        </section>

        <section class="card" style="margin-top:16px">
            <div class="sec-head" style="padding:20px 24px 0">
                <span class="sec-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <div class="t">{{ __('Budget Control') }}</div>
                    <div class="d">{{ __('Checked against the category budget before submission.') }}</div>
                </div>
            </div>
            <div style="padding:16px 24px 20px">
                <div class="li-totals">
                    @if($budget)
                        <div class="box">
                            <div class="trow"><span>{{ __('Annual') }}</span><span class="v">{{ format_money($budget['total_budgeted'], null, 2) }}</span></div>
                            <div class="trow"><span>{{ __('Used to date') }}</span><span class="v">{{ format_money($budget['total_spent'], null, 2) }}</span></div>
                            <div class="trow"><span>{{ __('This expense') }}</span><span class="v">{{ format_money($budget['total_requested'], null, 2) }}</span></div>
                            <div class="trow total"><span>{{ __('Remaining') }}</span><span class="v">{{ format_money($budget['total_available'], null, 2) }}</span></div>
                            @if(($budget['total_available'] ?? 0) >= 0)
                                <div class="okchip ok" style="margin-top:12px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M6 12.5l4 4 8-9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ __('Within Budget') }}</div>
                            @else
                                <div class="okchip warn" style="margin-top:12px"><svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 4 2 20h20L12 4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>{{ __('Over Budget') }}</div>
                                <div class="d" style="margin-top:8px;font-size:0.857rem;color:var(--warn,#d97706)">{{ __('An override (reason and approver) is required before this expense can be posted.') }}</div>
                            @endif
                        </div>
                    @else
                        <div class="box">
                            <div class="trow"><span>{{ __('Budget check') }}</span><span class="v">{{ __('Run automatically on submit.') }}</span></div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="card" style="margin-top:16px">
            <div class="sec-head" style="padding:20px 24px 0">
                <span class="sec-ic">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 5h14v14H5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="9" cy="10" r="1.4" fill="currentColor"/><path d="M5 17l5-4 4 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <div class="t">{{ __('Receipts & Attachments') }}</div>
                    <div class="d">{{ __('PDF, image or spreadsheet. Up to 10 files.') }}</div>
                </div>
            </div>

            @if($isEdit && $existingFiles->count())
                <div class="attchips" style="padding:16px 24px 0">
                    @foreach($existingFiles as $file)
                        <div class="att">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.8"/><path d="M9 8h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            <span class="name">{{ $file->original_name ?? basename($file->file_path ?? '') }}</span>
                            <label class="remove">
                                <input type="checkbox" name="delete_documents[]" value="{{ $file->id }}" />
                                {{ __('Remove') }}
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            <div style="padding:0 24px 20px">
                <div class="drop" id="exp-dropzone">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V5m0 0l-4 4m4-4l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <span>{{ __('Drag & drop files here, or') }} <label for="exp-files" class="link">{{ __('browse') }}</label></span>
                    <input id="exp-files" name="files[]" type="file" multiple hidden
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.xls,.xlsx,.doc,.docx,.txt,.csv" />
                </div>
                @error('files')<span class="err" style="display:block;margin-top:8px">{{ $message }}</span>@enderror
            </div>
        </section>
    </form>
</div>

<script>
    const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
    const COST_CENTER_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'cost-center']));
    const expenseAccounts = @json($expenseAccounts);
    const costCenters = @json($costCenters);
    const existingLines = @json($isEdit ? $expense->lines : []);
    let lineIndex = 0;

    function expenseAccountLabel(id) {
        const a = expenseAccounts.find(x => x.id == id);
        return a ? a.code + ' - ' + a.name : '';
    }

    function costCenterLabel(id) {
        const c = costCenters.find(x => x.id == id);
        return c ? c.code + ' - ' + c.name : '';
    }

    function updateTotals() {
        let subtotal = 0;
        let totalTax = 0;
        let totalDiscount = 0;
        document.querySelectorAll('#lines-body tr').forEach(row => {
            const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
            const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
            const disc = parseFloat(row.querySelector('[name*="[discount]"]').value) || 0;
            const taxRate = parseFloat(row.querySelector('[name*="[tax_rate]"]').value) || 0;
            const amount = qty * price - disc;
            const tax = amount * (taxRate / 100);
            subtotal += amount;
            totalTax += tax;
            totalDiscount += disc;
            row.querySelector('.line-total').textContent = (amount + tax).toFixed(2);
        });
        const total = subtotal + totalTax;
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total-tax').textContent = totalTax.toFixed(2);
        document.getElementById('total-discount').textContent = totalDiscount.toFixed(2);
        document.getElementById('grand-total').textContent = @json($cs) + total.toFixed(2);
        document.getElementById('alloc-total').textContent = total.toFixed(2);
    }

    function addLine(data) {
        const tbody = document.getElementById('lines-body');
        const idx = lineIndex++;
        const accountId = data ? data.expense_account_id : '';
        const costCenterId = data ? data.cost_center_id : '';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                ${scopedSearchFieldHtml({
                    name: `lines[${idx}][expense_account_id]`,
                    entity: 'account',
                    searchUrl: ACCOUNT_SEARCH_URL,
                    value: accountId,
                    label: accountId ? expenseAccountLabel(accountId) : '',
                    placeholder: 'Search accounts...',
                    required: true,
                })}
            </td>
            <td>
                <input type="text" name="lines[${idx}][description]" value="${data ? data.description : ''}" class="input" placeholder="{{ __('What was this for?') }}" />
            </td>
            <td>
                <input type="text" name="lines[${idx}][department]" value="${data ? data.department : ''}" class="input" placeholder="{{ __('Dept') }}" />
            </td>
            <td class="num">
                <input type="number" name="lines[${idx}][quantity]" value="${data ? data.quantity : 1}" min="0" step="any" class="input num" onchange="updateTotals()" oninput="updateTotals()" />
            </td>
            <td class="num">
                <input type="number" name="lines[${idx}][unit_price]" value="${data ? data.unit_price : 0}" min="0" step="0.01" class="input num" onchange="updateTotals()" oninput="updateTotals()" />
            </td>
            <td class="num">
                <input type="number" name="lines[${idx}][discount]" value="${data ? data.discount : 0}" min="0" step="0.01" class="input num" onchange="updateTotals()" oninput="updateTotals()" />
            </td>
            <td class="num">
                <input type="number" name="lines[${idx}][tax_rate]" value="${data ? data.tax_rate : 0}" min="0" max="100" step="0.01" class="input num" onchange="updateTotals()" oninput="updateTotals()" />
            </td>
            <td>
                ${scopedSearchFieldHtml({
                    name: `lines[${idx}][cost_center_id]`,
                    entity: 'cost-center',
                    searchUrl: COST_CENTER_SEARCH_URL,
                    value: costCenterId,
                    label: costCenterId ? costCenterLabel(costCenterId) : '',
                    placeholder: 'None',
                })}
            </td>
            <td class="num">
                <span class="line-total">0.00</span>
            </td>
            <td class="row-act">
                <button type="button" onclick="removeLine(this)" class="ibtn del" title="{{ __('Remove') }}">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function removeLine(btn) {
        btn.closest('tr').remove();
        updateTotals();
    }

    document.getElementById('add-line').addEventListener('click', function () { addLine(); });

    document.getElementById('expense-form').addEventListener('submit', function (e) {
        const rows = document.querySelectorAll('#lines-body tr');
        if (!rows.length) {
            e.preventDefault();
            window.CB.toast('error', '{{ __('Add at least one line.') }}');
            return;
        }
        const alloc = parseFloat(document.getElementById('alloc-total').textContent) || 0;
        const total = parseFloat(document.getElementById('grand-total').textContent.replace(/^\D+/, '')) || 0;
        if (Math.abs(alloc - total) > 0.005) {
            e.preventDefault();
            window.CB.toast('error', '{{ __('Allocation total must match the document total.') }}');
        }
    });

    if (existingLines.length > 0) {
        existingLines.forEach(function (line) { addLine(line); });
    } else {
        addLine();
    }

    updateTotals();

    (function () {
        const dz = document.getElementById('exp-dropzone');
        const input = document.getElementById('exp-files');
        dz.addEventListener('click', function (e) {
            if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
                input.click();
            }
        });
        dz.addEventListener('dragover', function (e) {
            e.preventDefault();
            dz.classList.add('over');
        });
        dz.addEventListener('dragleave', function () { dz.classList.remove('over'); });
        dz.addEventListener('drop', function (e) {
            e.preventDefault();
            dz.classList.remove('over');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    })();
</script>
