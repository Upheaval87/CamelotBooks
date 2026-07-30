<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Invoice') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.invoices.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Invoices') }}
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
            <form method="POST" action="{{ route('accounting.invoices.store') }}" id="invoice-form">
                @csrf

                <div class="card p-6 mb-6">
                    <x-form.section number="01" :title="__('Invoice Details')" />
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="customer_id" value="{{ __('Customer') }}" />
                            <div x-data="customerSearch('{{ old('customer_id') }}', '{{ old('customer_name', '') }}')" class="relative">
                                <input type="hidden" name="customer_id" :value="selectedId" />
                                <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query) open=true" @click.away="open=false" placeholder="Search customers..." autocomplete="off" class="input mt-1" />
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
                            <x-text-input id="invoice_date" name="invoice_date" type="date" class="mt-1 block w-full" :value="old('invoice_date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('invoice_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="Optional reference" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="memo" value="{{ __('Description') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
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
                                    <th>Cost Center</th>
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
                    <x-button variant="ghost" href="{{ route('accounting.invoices.index') }}">{{ __('Cancel') }}</x-button>
                    <x-primary-button type="submit">{{ __('Create Invoice') }}</x-primary-button>
                </div>
            </form>
            </div>
            <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                ['label' => __('Create'), 'links' => [
                    ['title' => __('New Customer'), 'route' => route('accounting.customers.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z\"/></svg>'],
                    ['title' => __('New Payment'), 'route' => route('accounting.customer-payments.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z\"/></svg>'],
                ]],
                ['label' => __('View'), 'links' => [
                    ['title' => __('Invoice List'), 'route' => route('accounting.invoices.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z\"/></svg>'],
                ]],
            ]" />
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
        let lineIndex = 0;

        function getLineTotal(row) {
            const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
            const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
            const discount = parseFloat(row.querySelector('[name*="[discount]"]').value) || 0;
            const taxRate = parseFloat(row.querySelector('[name*="[tax_rate]"]').value) || 0;
            const discounted = (qty * price) * (1 - discount / 100);
            const tax = discounted * (taxRate / 100);
            return discounted + tax;
        }

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

        function addLine() {
            const tbody = document.getElementById('lines-body');
            const idx = lineIndex++;
            const accountOptions = incomeAccounts.map(a =>
                `<option value="${a.id}">${a.code} - ${a.name}</option>`
            ).join('');
            const costCenterOptions = costCenters.map(c =>
                `<option value="${c.id}">${c.code} - ${c.name}</option>`
            ).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-2" style="min-width: 220px;">
                    <div x-data="searchableSelect({
                        name: 'lines[${idx}][product_id]',
                        items: products,
                        valueKey: 'id',
                        labelKey: 'name',
                        searchKeys: ['name', 'sku', 'barcode'],
                        showFields: ['sku', 'sales_price'],
                        preload: '',
                        preloadLabel: '',
                        onSelectCallback: null,
                        enableAdvancedSearch: true,
                        advancedSearchName: 'product_invoice',
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
                    <input type="text" name="lines[${idx}][description]" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm bg-gray-50" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity]" value="1" min="0" step="any" class="line-qty block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][unit_price]" value="0" min="0" step="0.01" readonly class="line-price block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right bg-gray-50" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][discount]" value="0" min="0" max="100" step="0.01" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <input type="hidden" name="lines[${idx}][tax_rate]" value="0" />
                <td class="px-4 py-2">
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
                if (acctInput) acctInput.value = item.income_account_id;
            }
            updateTotals();
        });

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateTotals();
        }

        addLine();
    </script>

    <x-advanced-search-modal name="product_invoice" :items="$products" labelKey="name" :showFields="['sku', 'sales_price']" :categories="$itemCategories ?? []" :types="['service', 'inventory', 'non_inventory']" />
</x-app-layout>
