<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $invoiceItems = collect($invoices)->map(fn($i) => ['id' => $i->id, 'label' => $i->invoice_number, 'subtitle' => ($i->customer?->name ?? '') . ' — ' . format_money($i->total)]);
        $selectedInvoice = old('invoice_id') ? $invoices->firstWhere('id', (int) old('invoice_id')) : null;
        $selectedCustomer = old('customer_id') ? $customers->firstWhere('id', (int) old('customer_id')) : null;
    @endphp

    <div class="suite py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Create Credit Note') }}</h1>
                    <div class="sub">{{ __('Issue credit to a customer for returns, discounts or adjustments.') }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.credit-notes.index') }}" class="btn ghost sm">{{ __('Cancel') }}</a>
                    <button type="submit" form="cn-form" class="btn cta">{{ __('Create Credit Note') }}</button>
                </div>
            </div>

            <form method="POST" action="{{ route('accounting.credit-notes.store') }}" id="cn-form" novalidate>
                @csrf

                <x-input-error :messages="$errors->get('error')" class="mb-4" />

                <div class="shell">
                    <div class="flex flex-col gap-5 min-w-0">

                        {{-- credit note info --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg></span>
                                <h2>{{ __('Credit Note Info') }}</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="g4">
                                <div class="field sp2">
                                    <label for="customer_id">Customer <span class="req">*</span></label>
                                    <x-scoped-search-field
                                        name="customer_id"
                                        entity="customer"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                        :value="old('customer_id')"
                                        :label="old('customer_name', $selectedCustomer?->name)"
                                        placeholder="Search customers..."
                                    />
                                    <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                                </div>
                                <div class="field sp2">
                                    <label for="invoice_id">Reference Invoice (Optional)</label>
                                    <x-scoped-search-field
                                        name="invoice_id"
                                        mode="client"
                                        :items="$invoiceItems"
                                        :value="old('invoice_id')"
                                        :label="old('invoice_id') ? ($selectedInvoice ? ($selectedInvoice->invoice_number . ' — ' . ($selectedInvoice->customer?->name ?? '') . ' — ' . format_money($selectedInvoice->total)) : '') : ''"
                                        placeholder="{{ __('No Invoice') }}"
                                    />
                                    <x-input-error :messages="$errors->get('invoice_id')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="credit_note_date">Credit Note Date <span class="req">*</span></label>
                                    <input id="credit_note_date" name="credit_note_date" type="date" class="input" value="{{ old('credit_note_date', now()->format('Y-m-d')) }}" required />
                                    <x-input-error :messages="$errors->get('credit_note_date')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="reference">Reference</label>
                                    <input id="reference" name="reference" type="text" class="input" value="{{ old('reference') }}" placeholder="Optional reference" />
                                    <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                                </div>
                                <div class="field sp3">
                                    <label for="reason">Reason</label>
                                    <input id="reason" name="reason" type="text" class="input" value="{{ old('reason') }}" placeholder="Reason for credit note" />
                                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                                </div>
                                <div class="field sp3">
                                    <label for="memo">Description</label>
                                    <textarea id="memo" name="memo" rows="3" class="input" placeholder="Internal notes">{{ old('memo') }}</textarea>
                                    <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        {{-- line items --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span>
                                <h2>{{ __('Line Items') }}</h2>
                                <span class="rule"></span>
                                <button type="button" id="add-line" class="btn sec sm">＋ {{ __('Add Line') }}</button>
                            </div>

                            <div class="li-wrap" style="margin-top:0">
                                <table id="lines-table" class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th style="width:16%">{{ __('Product') }}</th>
                                            <th style="width:20%">{{ __('Description') }}</th>
                                            <th class="num" style="width:7%">{{ __('Qty') }}</th>
                                            <th class="num" style="width:11%">{{ __('Unit Price') }} ({{ $cs }})</th>
                                            <th class="num" style="width:9%">{{ __('Tax %') }}</th>
                                            <th style="width:22%">{{ __('Income Account') }}</th>
                                            <th class="num" style="width:11%">{{ __('Line Total') }} ({{ $cs }})</th>
                                            <th class="num" style="width:4%">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200" id="lines-body">
                                    </tbody>
                                </table>
                            </div>

                            <x-input-error :messages="$errors->get('lines')" class="mt-2" />

                            <div class="li-totals" style="margin-top:16px">
                                <div class="box" style="max-width:22rem;margin-left:auto">
                                    <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v" id="subtotal">0.00</span></div>
                                    <div class="trow"><span>{{ __('Tax') }}</span><span class="v" id="total-tax">0.00</span></div>
                                    <div class="trow total"><span>{{ __('Total') }}</span><span class="v" id="grand-total">0.00</span></div>
                                </div>
                            </div>
                        </section>
                    </div>

                    {{-- rail --}}
                    <aside class="railsum">
                        <section class="card">
                            <div class="rail-sec">
                                <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7.5h8M8.5 12h.01M12 12h.01"/></svg></span><h2>{{ __('Summary') }}</h2></div>
                                <div style="margin-top:8px">
                                    <div class="srow"><span class="l">{{ __('Subtotal') }}</span><span class="v" id="rail-subtotal">0.00</span></div>
                                    <div class="srow"><span class="l">{{ __('Tax') }}</span><span class="v" id="rail-tax">0.00</span></div>
                                    <div class="srow strong"><span class="l">{{ __('Total') }}</span><span class="v" id="rail-total">0.00</span></div>
                                </div>
                                <div class="gt"><span class="l">{{ __('Total') }}</span><span class="v" id="rail-gt">0.00</span></div>
                            </div>
                            <div class="rail-sec">
                                <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.invoices.create') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/></svg></span>{{ __('New Invoice') }}</a>
                                    <a href="{{ route('accounting.customers.create') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg></span>{{ __('New Customer') }}</a>
                                    <a href="{{ route('accounting.credit-notes.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7 7-7M3 12h18"/></svg></span>{{ __('Credit Notes List') }}</a>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <script>
        @php
            $productsJson = $products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'sales_price' => $p->sales_price,
                'purchase_price' => $p->purchase_price,
                'type' => $p->type,
                'description' => $p->description,
                'tracked_as_inventory' => $p->tracked_as_inventory,
                'unit_price' => $p->sales_price,
                'tax_rate' => $p->tax_rate,
                'income_account_id' => $p->income_account_id,
            ]);
        @endphp
        const products = @json($productsJson);
        const incomeAccounts = @json($incomeAccounts);
        const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
        const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
        let lineIndex = 0;

        function incomeAccountLabel(id) {
            const a = incomeAccounts.find(x => x.id == id);
            return a ? a.code + ' - ' + a.name : '';
        }

        function updateTotals() {
            let subtotal = 0;
            let totalTax = 0;
            document.querySelectorAll('#lines-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
                const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
                const taxRate = parseFloat(row.querySelector('[name*="[tax_rate]"]').value) || 0;
                const lineSubtotal = qty * price;
                const lineTax = lineSubtotal * (taxRate / 100);
                subtotal += lineSubtotal;
                totalTax += lineTax;
                row.querySelector('.line-total').textContent = (lineSubtotal + lineTax).toFixed(2);
            });
            const total = subtotal + totalTax;
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('total-tax').textContent = totalTax.toFixed(2);
            document.getElementById('grand-total').textContent = total.toFixed(2);
            document.getElementById('rail-subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('rail-tax').textContent = totalTax.toFixed(2);
            document.getElementById('rail-total').textContent = total.toFixed(2);
            document.getElementById('rail-gt').textContent = total.toFixed(2);
        }

        function addLine() {
            const tbody = document.getElementById('lines-body');
            const idx = lineIndex++;
            const incomeAccountField = scopedSearchFieldHtml({
                name: `lines[${idx}][income_account_id]`,
                entity: 'account',
                searchUrl: ACCOUNT_SEARCH_URL,
                value: '',
                label: '',
                placeholder: 'Select Account',
            });
            const picker = scopedSearchFieldHtml({
                name: `lines[${idx}][product_id]`,
                entity: 'product',
                searchUrl: PRODUCT_SEARCH_URL,
                value: '',
                label: '',
                placeholder: 'Search products...',
            });
            const tr = document.createElement('tr');
            tr.setAttribute('data-line-idx', idx);
            tr.innerHTML = `
                <td>
                    ${picker}
                </td>
                <td>
                    <input type="text" name="lines[${idx}][description]" readonly class="input" />
                </td>
                <td>
                    <input type="number" name="lines[${idx}][quantity]" value="1" min="0" step="any" class="input" style="text-align:right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td>
                    <input type="number" name="lines[${idx}][unit_price]" value="0" min="0" step="0.01" readonly class="input" style="text-align:right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td><input type="hidden" name="lines[${idx}][tax_rate]" value="0" />
                    <input type="number" value="0" min="0" max="100" step="0.01" class="input" style="text-align:right" onchange="const r=this.closest('tr');r.querySelector('[name*="[tax_rate]"]').value=this.value;updateTotals()" oninput="const r=this.closest('tr');r.querySelector('[name*="[tax_rate]"]').value=this.value;updateTotals()" />
                </td>
                <td>
                    ${incomeAccountField}
                </td>
                <td class="numr line-total" style="font-weight:800">0.00</td>
                <td style="text-align:center">
                    <button type="button" onclick="removeLine(this)" class="ibtn del" title="Remove"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        document.getElementById('add-line').addEventListener('click', addLine);

        document.getElementById('lines-body').addEventListener('item-selected', function(e) {
            const row = e.target.closest('tr');
            if (!row) return;
            const item = e.detail.item;
            if (item.description) {
                const descInput = row.querySelector('[name*="[description]"]');
                if (descInput) descInput.value = item.description;
            }
            if (item.unit_price || item.sales_price) {
                const priceInput = row.querySelector('[name*="[unit_price]"]');
                if (priceInput) priceInput.value = item.unit_price || item.sales_price;
            }
            if (item.tax_rate) {
                const taxInput = row.querySelector('[name*="[tax_rate]"]');
                if (taxInput) taxInput.value = item.tax_rate;
                const visibleTax = row.querySelector('input[type="number"]');
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
            updateTotals();
        });

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateTotals();
        }

        addLine();
    </script>
</x-app-layout>
