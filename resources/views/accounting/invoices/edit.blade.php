<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Invoice') }} #{{ $invoice->invoice_number }}
            </h2>
            <a href="{{ route('accounting.invoices.show', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Invoice') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <form method="POST" action="{{ route('accounting.invoices.update', $invoice) }}" id="invoice-form">
                @csrf
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Invoice Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="customer_id" value="{{ __('Customer') }}" />
                            <div x-data="customerSearch('{{ old('customer_id', $invoice->customer_id) }}', '{{ old('customer_name', $invoice->customer?->name ?? '') }}')" class="relative">
                                <input type="hidden" name="customer_id" :value="selectedId" />
                                <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query) open=true" @click.away="open=false" placeholder="Search customers..." autocomplete="off" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div x-show="open && results.length > 0" x-cloak class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                    <template x-for="c in results" :key="c.id">
                                        <div @click="select(c)" class="px-3 py-2 cursor-pointer hover:bg-indigo-50 text-sm" x-text="c.name + (c.email ? ' (' + c.email + ')' : '')"></div>
                                    </template>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="invoice_date" value="{{ __('Invoice Date') }}" />
                            <x-text-input id="invoice_date" name="invoice_date" type="date" class="mt-1 block w-full" :value="old('invoice_date', $invoice->invoice_date?->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('invoice_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference', $invoice->reference)" placeholder="Optional reference" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="memo" value="{{ __('Memo') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo', $invoice->memo)" placeholder="Optional memo" />
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Center</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Line Total ({{ $cs }})</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body">
                            </tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
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
                    <a href="{{ route('accounting.invoices.show', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Update Invoice') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

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
        const costCenters = @json($costCenters);
        const existingLines = @json($invoice->lines);
        let lineIndex = 0;

        function updateTotals() {
            let subtotal = 0;
            let totalTax = 0;
            document.querySelectorAll('#lines-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
                const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
                const discount = parseFloat(row.querySelector('[name*="[discount]"]').value) || 0;
                const taxRate = parseFloat(row.querySelector('[name*="[tax_rate]"]').value) || 0;
                const lineSubtotal = (qty * price) * (1 - discount / 100);
                const lineTax = lineSubtotal * (taxRate / 100);
                subtotal += lineSubtotal;
                totalTax += lineTax;
                row.querySelector('.line-total').textContent = (lineSubtotal + lineTax).toFixed(2);
            });
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('total-tax').textContent = totalTax.toFixed(2);
            document.getElementById('grand-total').textContent = (subtotal + totalTax).toFixed(2);
        }

        function addLine(data = null) {
            const tbody = document.getElementById('lines-body');
            const idx = lineIndex++;
            const selectedId = data ? String(data.product_id || '') : '';
            const selectedName = data && data.product_id
                ? (products.find(p => p.id == data.product_id)?.name || '')
                : '';
            const accountOptions = incomeAccounts.map(a =>
                `<option value="${a.id}" ${data && data.income_account_id == a.id ? 'selected' : ''}>${a.code} - ${a.name}</option>`
            ).join('');
            const costCenterOptions = costCenters.map(c =>
                `<option value="${c.id}" ${data && data.cost_center_id == c.id ? 'selected' : ''}>${c.code} - ${c.name}</option>`
            ).join('');
            const tr = document.createElement('tr');
            tr.setAttribute('data-line-idx', idx);
            tr.innerHTML = `
                <td class="px-4 py-2" style="min-width: 220px;">
                    <div x-data="searchableSelect({
                        name: 'lines[${idx}][product_id]',
                        items: products,
                        valueKey: 'id',
                        labelKey: 'name',
                        searchKeys: ['name', 'sku', 'barcode'],
                        showFields: ['sku', 'sales_price'],
                        preload: '${selectedId}',
                        preloadLabel: '${selectedName}',
                        onSelectCallback: null,
                        enableAdvancedSearch: true,
                        advancedSearchName: 'product_invoice_edit',
                    })" class="relative">
                        <input type="hidden" name="lines[${idx}][product_id]" :value="selectedId" />
                        <div class="flex">
                            <input type="text" x-model="query"
                                @input.debounce.200ms="filter()"
                                @focus="if(query.length > 0) open = true"
                                @keydown.down.prevent="moveHighlight(1)"
                                @keydown.up.prevent="moveHighlight(-1)"
                                @keydown.enter.prevent="confirmHighlight()"
                                @keydown.escape="open = false"
                                @keydown.tab="open = false"
                                placeholder="Search products..." autocomplete="off"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-l-md shadow-sm text-sm" />
                            <button type="button" @click="openAdvancedSearch()"
                                class="px-2 bg-gray-50 border border-l-0 border-gray-300 rounded-r-md hover:bg-gray-100 focus:outline-none" title="Advanced Search">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </button>
                        </div>
                        <div x-show="open && results.length > 0" x-cloak
                            class="absolute z-30 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                            <template x-for="(item, idx2) in results" :key="item[valueKey]">
                                <div @click="select(item)" @mouseenter="highlightIndex = parseInt(idx2)"
                                    class="px-3 py-2 cursor-pointer flex justify-between items-center text-sm border-b border-gray-100 last:border-0"
                                    :style="parseInt(idx2) === highlightIndex ? 'background-color: #4f46e5; color: white;' : ''">
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-medium truncate" x-text="item[labelKey]"></span>
                                        <div class="flex gap-2 text-xs" :style="parseInt(idx2) === highlightIndex ? 'color: #c7d2fe;' : 'color: #6b7280;'">
                                            <span x-show="item.sku" x-text="item.sku"></span>
                                            <span x-show="item.sales_price" x-text="formatNumber(parseFloat(item.sales_price))"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-2">
                    <input type="text" name="lines[${idx}][description]" value="${data ? (data.description || '') : ''}" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm bg-gray-50" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity]" value="${data ? data.quantity : 1}" min="0" step="any" class="line-qty block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][unit_price]" value="${data ? data.unit_price : 0}" min="0" step="0.01" readonly class="line-price block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right bg-gray-50" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][discount]" value="${data ? data.discount : 0}" min="0" max="100" step="0.01" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2"><input type="hidden" name="lines[${idx}][tax_rate]" value="0" />
                    <select name="lines[${idx}][income_account_id]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">Select Account</option>
                        ${accountOptions}
                    </select>
                </td>
                <td class="px-4 py-2">
                    <select name="lines[${idx}][cost_center_id]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">None</option>
                        ${costCenterOptions}
                    </select>
                </td>
                <td class="px-4 py-2 text-right text-sm font-medium line-total">0.00</td>
                <td class="px-4 py-2 text-center">
                    <button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        document.getElementById('add-line').addEventListener('click', () => addLine());

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
                if (acctInput) acctInput.value = item.income_account_id;
            }
            updateTotals();
        });

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateTotals();
        }

        existingLines.forEach(line => addLine(line));
        if (existingLines.length === 0) addLine();
    </script>

    <x-advanced-search-modal name="product_invoice_edit" :items="$products" labelKey="name" :showFields="['sku', 'sales_price']" :categories="$itemCategories ?? []" :types="['service', 'inventory', 'non_inventory']" />
</x-app-layout>
