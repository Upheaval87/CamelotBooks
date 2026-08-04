<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Edit Requisition') }} #{{ $requisition->requisition_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <form method="POST" action="{{ route('accounting.purchase-requisitions.update', $requisition) }}">
                        @csrf
                        @method('PUT')

                        <div class="card p-6 mb-6">
                            <x-form.section number="01" :title="__('Requisition Details')" />
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="date" value="{{ __('Date') }}" />
                                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', $requisition->date?->format('Y-m-d'))" required />
                                </div>
                                <div>
                                    <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                                    <x-scoped-search-field
                                        name="branch_id"
                                        entity="branch"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                        :value="old('branch_id', $requisition->branch_id)"
                                        :label="old('branch_id', $requisition->branch_id) ? ($branches->firstWhere('id', (int) old('branch_id', $requisition->branch_id))?->name ?? '') : ''"
                                        placeholder="{{ __('None') }}"
                                    />
                                </div>
                                <div>
                                    <x-input-label for="cost_center_id" value="{{ __('Cost Center') }}" />
                                    <x-scoped-search-field
                                        name="cost_center_id"
                                        entity="cost-center"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                        :value="old('cost_center_id', $requisition->cost_center_id)"
                                        :label="old('cost_center_id', $requisition->cost_center_id) ? ($costCenters->firstWhere('id', (int) old('cost_center_id', $requisition->cost_center_id))?->name ?? '') : ''"
                                        placeholder="{{ __('None') }}"
                                    />
                                </div>
                                <div class="col-span-4">
                                    <x-input-label for="memo" value="{{ __('Description') }}" />
                                    <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo', $requisition->memo)" />
                                </div>
                            </div>
                        </div>

                        <div class="card p-6 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <x-form.section number="02" :title="__('Line Items')" />
                                <x-button variant="ghost" type="button" id="add-line">
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
                                            <th class="text-right">Est. Unit Cost ({{ $cs }})</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"                        >Est. Total ({{ $cs }})</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="lines-body">
                                    </tbody>
                                </table>
                            </div>
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

                        <div class="flex justify-end mt-8 gap-3">
                            <x-button variant="ghost" href="{{ route('accounting.purchase-requisitions.show', $requisition) }}">{{ __('Cancel') }}</x-button>
                            <x-primary-button type="submit">{{ __('Update Requisition') }}</x-primary-button>
                        </div>
                    </form>
                </div>
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
            ]);
        @endphp
        const products = @json($productsJson);
        const existingLines = @json($requisition->lines);
        const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
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
            const picker = scopedSearchFieldHtml({
                name: `lines[${idx}][product_id]`,
                entity: 'product',
                searchUrl: PRODUCT_SEARCH_URL,
                value: selectedId,
                label: selectedName,
                placeholder: 'Search products...',
            });
            const tr = document.createElement('tr');
            tr.setAttribute('data-line-idx', idx);
            tr.innerHTML = `
                <td class="px-4 py-2" style="min-width: 220px;">
                    ${picker}
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

        function removeLine(btn) {
            btn.closest('tr').remove();
        }

        existingLines.forEach(line => addLine(line));
        if (existingLines.length === 0) addLine();
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
    </script>
</x-app-layout>
