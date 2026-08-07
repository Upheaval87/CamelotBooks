<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="Create Sales Receipt" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.sales-receipts.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Sales Receipts') }}
                </x-button>
            </div>
            <div class="form-page">
                <div class="form-page-main">
            <form method="POST" action="{{ route('accounting.sales-receipts.store') }}" id="receipt-form">
                @csrf

                <div class="card p-6 mb-6">
                    <x-form.section number="01" :title="__('Receipt Details')" />
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="customer_id" value="{{ __('Customer (optional)') }}" />
                            <x-scoped-search-field
                                name="customer_id"
                                entity="customer"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                :value="old('customer_id')"
                                :label="old('customer_name')"
                                placeholder="Search customers..."
                                :required="false"
                            />
                        </div>
                        <div>
                            <x-input-label for="receipt_date" value="{{ __('Receipt Date') }}" />
                            <x-text-input id="receipt_date" name="receipt_date" type="date" class="mt-1 block w-full" :value="old('receipt_date', now()->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="Optional reference" />
                        </div>
                        <div>
                            <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                            <x-scoped-search-field
                                name="branch_id"
                                entity="branch"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                :value="old('branch_id')"
                                :label="old('branch_id') ? ($branches->firstWhere('id', (int) old('branch_id'))?->name ?? '') : ''"
                                placeholder="{{ __('None') }}"
                            />
                        </div>
                        <div class="col-span-4">
                            <x-input-label for="memo" value="{{ __('Description') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
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
                                    <th class="text-right">Discount %</th>
                                    <th>Income Account</th>
                                    <th class="text-right">Line Total ({{ $cs }})</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body"></tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>

                <div class="card p-6 mb-6">
                    <x-form.section number="03" :title="__('Payments')" />
                    <div x-data="paymentSection()" id="payments-section">
                        <template x-for="(payment, pIdx) in payments" :key="pIdx">
                            <div class="flex items-end gap-3 mb-3 w-full">
                                <input type="hidden" :name="'payments[' + pIdx + '][institution]'" x-model="payment.provider_id" />
                                <div class="w-48 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Payment Method</label>
                                    <select :name="'payments[' + pIdx + '][payment_method_id]'" x-model="payment.payment_method_id" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm" @change="onMethodChange(pIdx)">
                                        <option value="">Select method...</option>
                                        @foreach($paymentMethods as $pm)
                                            <option value="{{ $pm->id }}" data-type="{{ $pm->type }}" data-clearing="{{ $pm->clearing_account_id }}">{{ $pm->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="payment.type === 'mobile_money'" class="w-44 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Provider</label>
                                    <select x-model="payment.provider_id" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm">
                                        <option value="">Select provider...</option>
                                        @foreach($mobileProviders as $mp)
                                            <option value="{{ $mp->name }}">{{ $mp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Amount ({{ $cs }})</label>
                                    <input type="number" :name="'payments[' + pIdx + '][amount]'" x-model.number="payment.amount" step="0.01" min="0" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right" @input="updateRemaining()" />
                                </div>
                                <div x-show="payment.type === 'cash'" class="w-44 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Cash Tendered</label>
                                    <input type="number" :name="'payments[' + pIdx + '][cash_tendered]'" x-model.number="payment.cash_tendered" step="0.01" min="0" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right" />
                                </div>
                                <div x-show="payment.type === 'bank_transfer' || payment.type === 'card'" class="w-44 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Reference</label>
                                    <input type="text" :name="'payments[' + pIdx + '][reference_number]'" x-model="payment.reference_number" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm" />
                                </div>
                                <div class="shrink-0 flex items-end pb-1">
                                    <button type="button" @click="removePayment(pIdx)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="addPayment()" class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            Add Payment
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('payments')" class="mt-2" />
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
                    <x-button variant="ghost" href="{{ route('accounting.sales-receipts.index') }}">{{ __('Cancel') }}</x-button>
                    <x-primary-button type="submit">{{ __('Create Sales Receipt') }}</x-primary-button>
                </div>
            </form>
            </div>
            <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                ['label' => __('Create'), 'links' => [
                    ['title' => __('New Customer'), 'route' => route('accounting.customers.create'), 'icon' => 'person'],
                ]],
                ['label' => __('View'), 'links' => [
                    ['title' => __('Receipts List'), 'route' => route('accounting.sales-receipts.index'), 'icon' => 'document'],
                ]],
            ]" />
        </div>
    </div>

    @php
        $productsJson = $products->map(fn($p) => [
            'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'barcode' => $p->barcode,
            'sales_price' => $p->sales_price, 'purchase_price' => $p->purchase_price,
            'type' => $p->type, 'description' => $p->description,
            'tracked_as_inventory' => $p->tracked_as_inventory,
            'income_account_id' => $p->income_account_id, 'tax_rate' => $p->tax_rate,
        ])->values();
    @endphp

    <script>
        const products = @json($productsJson);
        const incomeAccounts = @json($incomeAccounts);
        const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
        const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
        let lineIndex = 0;

        function incomeAccountLabel(id) {
            const a = incomeAccounts.find(function(x) { return x.id == id; });
            return a ? a.code + ' - ' + a.name : '';
        }

        function updateTotals() {
            let subtotal = 0;
            let totalTax = 0;
            document.querySelectorAll('#lines-body tr').forEach(function(row) {
                var qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
                var price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
                var discount = parseFloat(row.querySelector('[name*="[discount]"]').value) || 0;
                var taxRate = parseFloat(row.querySelector('[name*="[tax_rate]"]').value) || 0;
                var lineSubtotal = (qty * price) * (1 - discount / 100);
                var lineTax = lineSubtotal * (taxRate / 100);
                subtotal += lineSubtotal;
                totalTax += lineTax;
                row.querySelector('.line-total').textContent = (lineSubtotal + lineTax).toFixed(2);
            });
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('total-tax').textContent = totalTax.toFixed(2);
            document.getElementById('grand-total').textContent = (subtotal + totalTax).toFixed(2);
        }

        function addLine() {
            var tbody = document.getElementById('lines-body');
            var idx = lineIndex++;
            var incomeAccountField = scopedSearchFieldHtml({
                name: 'lines[' + idx + '][income_account_id]',
                entity: 'account',
                searchUrl: ACCOUNT_SEARCH_URL,
                value: '',
                label: '',
                placeholder: 'Select Account',
            });
            var tr = document.createElement('tr');
            var picker = scopedSearchFieldHtml({
                name: 'lines[' + idx + '][product_id]',
                entity: 'product',
                searchUrl: PRODUCT_SEARCH_URL,
                value: '',
                label: '',
                placeholder: 'Search products...',
            });
            tr.innerHTML =
                '<td class="px-4 py-2" style="min-width: 220px;">' + picker + '</td>' +
                '<td class="px-4 py-2"><input type="text" name="lines[' + idx + '][description]" value="" readonly class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm bg-gray-50" /></td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][quantity]" value="1" min="0" step="any" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right" oninput="updateTotals()" /></td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][unit_price]" value="0" min="0" step="0.01" readonly class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right bg-gray-50" oninput="updateTotals()" /></td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][discount]" value="0" min="0" max="100" step="0.01" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right" oninput="updateTotals()" /></td>' +
                '<td class="px-4 py-2"><input type="hidden" name="lines[' + idx + '][tax_rate]" value="0" />' + incomeAccountField + '</td>' +
                '<td class="px-4 py-2 text-right text-sm font-medium line-total">0.00</td>' +
                '<td class="px-4 py-2 text-center"><button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
            tbody.appendChild(tr);
            updateTotals();
        }

        document.getElementById('lines-body').addEventListener('item-selected', function(e) {
            var row = e.target.closest('tr');
            if (!row) return;
            var item = e.detail.item;
            if (item.description) {
                var descInput = row.querySelector('[name*="[description]"]');
                if (descInput) descInput.value = item.description;
            }
            if (item.sales_price) {
                var priceInput = row.querySelector('[name*="[unit_price]"]');
                if (priceInput) priceInput.value = parseFloat(item.sales_price).toFixed(2);
            }
            if (item.tax_rate !== undefined && item.tax_rate !== null) {
                var taxInput = row.querySelector('[name*="[tax_rate]"]');
                if (taxInput) taxInput.value = parseFloat(item.tax_rate).toFixed(2);
            }
            if (item.income_account_id) {
                var acctInput = row.querySelector('[name*="[income_account_id]"]');
                if (acctInput) {
                    var accountItem = incomeAccounts.find(function(a) { return a.id == item.income_account_id; });
                    scopedSearchFieldSet(acctInput, 'account', {
                        id: item.income_account_id,
                        label: accountItem ? accountItem.code + ' - ' + accountItem.name : ''
                    });
                }
            }
            updateTotals();
        });

        function removeLine(btn) { btn.closest('tr').remove(); updateTotals(); }

        document.getElementById('add-line').addEventListener('click', function() { addLine(); });
        addLine();

        function paymentSection() {
            return {
                payments: [{ payment_method_id: '', amount: 0, type: '', cash_tendered: 0, reference_number: '', provider_id: '' }],
                addPayment() {
                    this.payments.push({ payment_method_id: '', amount: 0, type: '', cash_tendered: 0, reference_number: '', provider_id: '' });
                },
                removePayment(idx) {
                    this.payments.splice(idx, 1);
                },
                onMethodChange(idx) {
                    var sel = document.querySelectorAll('#payments-section select[x-model="payment.payment_method_id"]')[idx];
                    if (!sel) return;
                    var opt = sel.options[sel.selectedIndex];
                    this.payments[idx].type = opt.dataset.type || '';
                    if (this.payments[idx].type !== 'mobile_money') {
                        this.payments[idx].provider_id = '';
                    }
                },
                updateRemaining() {}
            }
        }
    </script>
</x-app-layout>
