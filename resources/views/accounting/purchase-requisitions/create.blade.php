<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Purchase Requisition') }}
            </h2>
            <a href="{{ route('accounting.purchase-requisitions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('accounting.purchase-requisitions.store') }}" id="requisition-form">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Requisition Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="date" value="{{ __('Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="branch_id" value="{{ __('Branch (Optional)') }}" />
                            <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">None</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="cost_center_id" value="{{ __('Cost Center (Optional)') }}" />
                            <select id="cost_center_id" name="cost_center_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">None</option>
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}" {{ old('cost_center_id') == $cc->id ? 'selected' : '' }}>
                                        {{ $cc->code }} - {{ $cc->name }}
                                    </option>
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
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Est. Unit Cost</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"                        >Est. Total ({{ $cs }})</th>
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
                    <a href="{{ route('accounting.purchase-requisitions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Create Requisition') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <x-advanced-search-modal name="product_purchase" :items="$products" labelKey="name" :showFields="['sku', 'sales_price', 'stock_qty']" :categories="$itemCategories ?? []" :types="['service', 'inventory', 'non_inventory']" />

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
            ]);
        @endphp
        const products = @json($productsJson);
        let lineIndex = 0;



        function updateTotals() {
            let subtotal = 0;
            document.querySelectorAll('#lines-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
                const price = parseFloat(row.querySelector('[name*="[estimated_unit_cost]"]').value) || 0;
                const lineTotal = qty * price;
                row.querySelector('.line-total').textContent = lineTotal.toFixed(2);
                subtotal += lineTotal;
            });
            document.getElementById('subtotal').textContent = formatNumber(subtotal);
            document.getElementById('total-tax').textContent = formatNumber(0);
            document.getElementById('grand-total').textContent = formatNumber(subtotal);
        }

        function addLine(data = null) {
            const tbody = document.getElementById('lines-body');
            const idx = lineIndex++;
            const selectedId = data ? String(data.product_id || '') : '';
            const selectedName = data && data.product_id
                ? (products.find(p => p.id == data.product_id)?.name || '')
                : '';
            const xDataAttr = 'searchableSelect({' +
                'name: \'lines[' + idx + '][product_id]\',' +
                'items: products,' +
                'valueKey: \'id\',' +
                'labelKey: \'name\',' +
                'searchKeys: [\'name\', \'sku\', \'barcode\'],' +
                'showFields: [\'sku\', \'purchase_price\'],' +
                'preload: \'' + selectedId + '\',' +
                'preloadLabel: \'' + selectedName + '\',' +
                'onSelectCallback: null,' +
                'enableAdvancedSearch: true,' +
                'advancedSearchName: \'product_purchase\',' +
            '})';
            const tr = document.createElement('tr');
            tr.setAttribute('data-line-idx', idx);
            tr.innerHTML = `
                <td class="px-4 py-2" style="min-width: 220px;">
                    <div x-data="${escapeHtml(xDataAttr)}" class="relative">
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
                    <input type="text" name="lines[${idx}][description]" value="${data ? (data.description || '') : ''}" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity]" value="${data ? data.quantity : 1}" min="0.01" step="any" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" required />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][estimated_unit_cost]" value="${data ? (data.estimated_unit_cost || 0) : 0}" min="0" step="0.01" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2 text-right text-sm font-medium line-total">${data ? (data.estimated_total || 0) : 0}</td>
                <td class="px-4 py-2 text-center">
                    <button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function removeLine(btn) {
            btn.closest('tr').remove();
        }

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
                var costInput = row.querySelector('[name*="[estimated_unit_cost]"]');
                if (costInput && (!costInput.value || parseFloat(costInput.value) === 0)) {
                    costInput.value = parseFloat(item.purchase_price).toFixed(2);
                }
            }
            updateTotals();
        });
        addLine();
    </script>
</x-app-layout>
