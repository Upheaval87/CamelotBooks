<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Create Credit Note') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.credit-notes.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Credit Notes') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
            <form method="POST" action="{{ route('accounting.credit-notes.store') }}">
                @csrf

                <div class="card p-6 mb-6">
                    <x-form.section number="01" :title="__('Credit Note Details')" />
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="customer_id" value="{{ __('Customer') }}" />
                            <x-scoped-search-field
                                name="customer_id"
                                entity="customer"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                :value="old('customer_id')"
                                :label="old('customer_name')"
                                placeholder="Search customers..."
                            />
                            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="invoice_id" value="{{ __('Reference Invoice (Optional)') }}" />
                            @php $invoiceItems = collect($invoices)->map(fn($i) => ['id' => $i->id, 'label' => $i->invoice_number, 'subtitle' => ($i->customer?->name ?? '') . ' — ' . format_money($i->total)]); @endphp
                            @php $selectedInvoice = old('invoice_id') ? $invoices->firstWhere('id', (int) old('invoice_id')) : null; @endphp
                            <x-scoped-search-field
                                name="invoice_id"
                                mode="client"
                                :items="$invoiceItems"
                                :value="old('invoice_id')"
                                :label="$selectedInvoice ? ($selectedInvoice->invoice_number . ' — ' . ($selectedInvoice->customer?->name ?? '') . ' — ' . format_money($selectedInvoice->total)) : ''"
                                placeholder="{{ __('No Invoice') }}"
                            />
                            <x-input-error :messages="$errors->get('invoice_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="credit_note_date" value="{{ __('Credit Note Date') }}" />
                            <x-text-input id="credit_note_date" name="credit_note_date" type="date" class="mt-1 block w-full" :value="old('credit_note_date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('credit_note_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="Optional reference" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="reason" value="{{ __('Reason') }}" />
                            <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason')" placeholder="Reason for credit note" />
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <x-form.section number="02" :title="__('Line Items')" />
                        <button type="button" id="add-line" class="btn-add">
                            {{ __('Add Line') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="lines-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Description</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price ({{ $cs }})</th>

                                    <th>Income Account</th>
                                    <th class="text-right">Line Total ({{ $cs }})</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body">
                            </tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>

                <div class="card p-6 mb-6">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Subtotal:</span>
                                <span id="subtotal" class="text-gray-900">0.00</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tax:</span>
                                <span id="total-tax" class="text-gray-900">0.00</span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold border-t pt-2">
                                <span class="text-gray-800">Total:</span>
                                <span id="grand-total" class="text-gray-900">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-8 gap-3">
                    <x-button variant="ghost" href="{{ route('accounting.credit-notes.index') }}">{{ __('Cancel') }}</x-button>
                    <x-primary-button type="submit">{{ __('Create Credit Note') }}</x-primary-button>
                </div>
            </form>
                </div>
                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Invoice'), 'route' => route('accounting.invoices.create'), 'icon' => 'document'],
                        ['title' => __('New Customer'), 'route' => route('accounting.customers.create'), 'icon' => 'person'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Credit Notes List'), 'route' => route('accounting.credit-notes.index'), 'icon' => 'document'],
                    ]],
                ]" />
            </div>
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
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('total-tax').textContent = totalTax.toFixed(2);
            document.getElementById('grand-total').textContent = (subtotal + totalTax).toFixed(2);
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
                <td class="px-4 py-2" style="min-width: 220px;">
                    ${picker}
                </td>
                <td class="px-4 py-2">
                    <input type="text" name="lines[${idx}][description]" readonly class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm bg-gray-50" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity]" value="1" min="0" step="any" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][unit_price]" value="0" min="0" step="0.01" readonly class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right bg-gray-50" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2"><input type="hidden" name="lines[${idx}][tax_rate]" value="0" />
                    ${incomeAccountField}
                </td>
                <td class="px-4 py-2 text-right text-sm font-medium line-total">0.00</td>
                <td class="px-4 py-2 text-center">
                    <button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
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
