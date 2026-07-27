<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Sales Receipt</h2>
            <a href="{{ route('accounting.sales-receipts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Sales Receipts') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.sales-receipts.store') }}" id="receipt-form">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Receipt Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="customer_id" value="{{ __('Customer (optional)') }}" />
                            <div x-data="customerSearch('{{ old('customer_id') }}', '{{ old('customer_name', '') }}')" class="relative">
                                <input type="hidden" name="customer_id" :value="selectedId" />
                                <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query) open=true" @click.away="open=false" placeholder="Search customers..." autocomplete="off" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div x-show="open && results.length > 0" x-cloak class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                    <template x-for="c in results" :key="c.id">
                                        <div @click="select(c)" class="px-3 py-2 cursor-pointer hover:bg-indigo-50 text-sm" x-text="c.name + (c.email ? ' (' + c.email + ')' : '')"></div>
                                    </template>
                                </div>
                            </div>
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
                            <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">None</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="memo" value="{{ __('Memo') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Line Items') }}</h3>
                        <button type="button" id="add-line" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Add Line') }}
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="lines-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price ({{ $cs }})</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Discount %</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Line Total ({{ $cs }})</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body"></tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payments') }}</h3>
                    <div x-data="paymentSection()" id="payments-section">
                        <template x-for="(payment, pIdx) in payments" :key="pIdx">
                            <div class="flex items-end gap-3 mb-3 w-full">
                                <input type="hidden" :name="'payments[' + pIdx + '][institution]'" x-model="payment.provider_id" />
                                <div class="w-48 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Payment Method</label>
                                    <select :name="'payments[' + pIdx + '][payment_method_id]'" x-model="payment.payment_method_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" @change="onMethodChange(pIdx)">
                                        <option value="">Select method...</option>
                                        @foreach($paymentMethods as $pm)
                                            <option value="{{ $pm->id }}" data-type="{{ $pm->type }}" data-clearing="{{ $pm->clearing_account_id }}">{{ $pm->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="payment.type === 'mobile_money'" class="w-44 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Provider</label>
                                    <select x-model="payment.provider_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">Select provider...</option>
                                        @foreach($mobileProviders as $mp)
                                            <option value="{{ $mp->name }}">{{ $mp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Amount ({{ $cs }})</label>
                                    <input type="number" :name="'payments[' + pIdx + '][amount]'" x-model.number="payment.amount" step="0.01" min="0" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" @input="updateRemaining()" />
                                </div>
                                <div x-show="payment.type === 'cash'" class="w-44 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Cash Tendered</label>
                                    <input type="number" :name="'payments[' + pIdx + '][cash_tendered]'" x-model.number="payment.cash_tendered" step="0.01" min="0" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" />
                                </div>
                                <div x-show="payment.type === 'bank_transfer' || payment.type === 'card'" class="w-44 shrink-0">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Reference</label>
                                    <input type="text" :name="'payments[' + pIdx + '][reference_number]'" x-model="payment.reference_number" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
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

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
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

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.sales-receipts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Create Sales Receipt') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <x-advanced-search-modal name="product_receipt" :items="$products" labelKey="name" :showFields="['sku', 'sales_price']" :categories="$itemCategories ?? []" :types="['service', 'inventory', 'non_inventory']" />

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
        function customerSearch(selectedId, selectedName) {
            return {
                query: selectedName || '', selectedId: selectedId || '', results: [], open: false,
                async search() {
                    if (this.query.length < 1) { this.results = []; this.open = false; return; }
                    const r = await fetch('{{ route("accounting.customers.search") }}?q=' + encodeURIComponent(this.query));
                    this.results = await r.json(); this.open = true;
                },
                select(c) { this.selectedId = c.id; this.query = c.name; this.open = false; }
            }
        }

        const products = @json($productsJson);
        const incomeAccounts = @json($incomeAccounts);
        let lineIndex = 0;

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
            var accountOptions = incomeAccounts.map(function(a) {
                return '<option value="' + a.id + '">' + a.code + ' - ' + a.name + '</option>';
            }).join('');
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="px-4 py-2" style="min-width: 220px;">' +
                    '<div x-data="searchableSelect({' +
                        'name: \'lines[' + idx + '][product_id]\',' +
                        'items: products,' +
                        'valueKey: \'id\',' +
                        'labelKey: \'name\',' +
                        'searchKeys: [\'name\', \'sku\', \'barcode\'],' +
                        'showFields: [\'sku\', \'sales_price\'],' +
                        'preload: \'\',' +
                        'preloadLabel: \'\',' +
                        'onSelectCallback: null,' +
                        'enableAdvancedSearch: true,' +
                        'advancedSearchName: \'product_receipt\',' +
                    '})" class="relative">' +
                        '<input type="hidden" name="lines[' + idx + '][product_id]" :value="selectedId" />' +
                        '<div class="flex">' +
                            '<input type="text" x-model="query" @input.debounce.200ms="filter()" @focus="if(query.length > 0) open = true" @keydown.down.prevent="moveHighlight(1)" @keydown.up.prevent="moveHighlight(-1)" @keydown.enter.prevent="confirmHighlight()" @keydown.escape="open = false" @keydown.tab="open = false" placeholder="Search products..." autocomplete="off" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-l-md shadow-sm text-sm" />' +
                            '<button type="button" @click="openAdvancedSearch()" class="px-2 bg-gray-50 border border-l-0 border-gray-300 rounded-r-md hover:bg-gray-100 focus:outline-none" title="Advanced Search">' +
                                '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>' +
                            '</button>' +
                        '</div>' +
                        '<div x-show="open && results.length > 0" x-cloak class="absolute z-30 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">' +
                            '<template x-for="(item, idx2) in results" :key="item[valueKey]">' +
                                '<div @click="select(item)" @mouseenter="highlightIndex = parseInt(idx2)" class="px-3 py-2 cursor-pointer flex justify-between items-center text-sm border-b border-gray-100 last:border-0" :style="parseInt(idx2) === highlightIndex ? \'background-color: #4f46e5; color: white;\' : \'\'">' +
                                    '<div class="flex flex-col min-w-0">' +
                                        '<span class="font-medium truncate" x-text="item[labelKey]"></span>' +
                                        '<div class="flex gap-2 text-xs" :style="parseInt(idx2) === highlightIndex ? \'color: #c7d2fe;\' : \'color: #6b7280;\'">' +
                                            '<span x-show="item.sku" x-text="item.sku"></span>' +
                                            '<span x-show="item.sales_price" x-text="formatNumber(parseFloat(item.sales_price))"></span>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</template>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="px-4 py-2"><input type="text" name="lines[' + idx + '][description]" value="" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm bg-gray-50" /></td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][quantity]" value="1" min="0" step="any" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" oninput="updateTotals()" /></td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][unit_price]" value="0" min="0" step="0.01" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right bg-gray-50" oninput="updateTotals()" /></td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][discount]" value="0" min="0" max="100" step="0.01" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" oninput="updateTotals()" /></td>' +
                '<td class="px-4 py-2"><input type="hidden" name="lines[' + idx + '][tax_rate]" value="0" /><select name="lines[' + idx + '][income_account_id]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"><option value="">Select Account</option>' + accountOptions + '</select></td>' +
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
                if (acctInput) acctInput.value = item.income_account_id;
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
