<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Goods Received Note') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.goods-received-notes.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </x-button>
            </div>
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
            <form method="POST" action="{{ route('accounting.goods-received-notes.store') }}" id="grn-form">
                @csrf

                <div class="card p-6 mb-6">
                    <x-form.section number="01" :title="__('GRN Details')" />
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                            <select id="vendor_id" name="vendor_id" class="input mt-1" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="purchase_order_id" value="{{ __('Purchase Order (Optional)') }}" />
                            <select id="purchase_order_id" name="purchase_order_id" class="input mt-1">
                                <option value="">None (Standalone)</option>
                                @foreach($purchaseOrders as $po)
                                    <option value="{{ $po->id }}" data-po='@json($po)' {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                                        {{ $po->po_number }} - {{ $po->vendor->name }} ({{ $po->status }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="date" value="{{ __('Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="branch_id" value="{{ __('Branch (Optional)') }}" />
                            <select id="branch_id" name="branch_id" class="input mt-1">
                                <option value="">None</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-4">
                            <x-input-label for="memo" value="{{ __('Description') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                        </div>
                    </div>
                </div>

                <div class="card p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <x-form.section number="02" :title="__('Received Items')" />
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
                                    <th class="text-right">Qty Received</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"                        >Unit Cost ({{ $cs }})</th>
                                    <th>Expense Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"                        >Total Cost ({{ $cs }})</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body"></tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>

                <div class="card p-6 mb-6">
                    <div class="flex justify-end">
                        <div class="w-48 space-y-2">
                            <div class="flex justify-between text-sm font-semibold border-t pt-2">
                                <span class="text-gray-800">Total:</span>
                                <span id="grand-total" class="text-gray-900">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-8 gap-3">
                    <x-button variant="ghost" href="{{ route('accounting.goods-received-notes.index') }}">{{ __('Cancel') }}</x-button>
                    <x-primary-button type="submit">{{ __('Create GRN') }}</x-primary-button>
                </div>
            </form>
                </div>
                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Purchase Order'), 'route' => route('accounting.purchase-orders.create'), 'icon' => 'document'],
                        ['title' => __('New Bill'), 'route' => route('accounting.bills.create'), 'icon' => 'document'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('GRN List'), 'route' => route('accounting.goods-received-notes.index'), 'icon' => 'document'],
                    ]],
                ]" />
            </div>
        </div>
    </div>

    <x-advanced-search-modal name="product_purchase" :items="$products" labelKey="name" :showFields="['sku', 'purchase_price']" :categories="$itemCategories ?? []" :types="['service', 'inventory', 'non_inventory']" />

    @php
        $productsJson = $products->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'sales_price' => $p->sales_price,
                'purchase_price' => $p->purchase_price,
                'type' => $p->type,
                'description' => $p->description,
                'tracked_as_inventory' => $p->tracked_as_inventory,
                'income_account_id' => $p->income_account_id,
                'expense_account_id' => $p->expense_account_id ?? null,
                'tax_rate' => $p->tax_rate,
            ];
        })->values();
    @endphp

    <script>
        const products = @json($productsJson);
        const expenseAccounts = @json($accounts);
        const selectedPo = @json($selectedPo);
        let lineIndex = 0;

        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }



        function updateTotals() {
            let total = 0;
            document.querySelectorAll('#lines-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('[name*="[quantity_received]"]').value) || 0;
                const cost = parseFloat(row.querySelector('[name*="[unit_cost]"]').value) || 0;
                const amt = qty * cost;
                row.querySelector('.line-total').textContent = amt.toFixed(2);
                total += amt;
            });
            document.getElementById('grand-total').textContent = total.toFixed(2);
        }

        function addLine(data) {
            var tbody = document.getElementById('lines-body');
            var idx = lineIndex++;
            var selectedId = data ? String(data.product_id || '') : '';
            var selectedName = data && data.product_id
                ? (products.find(function(p) { return p.id == data.product_id; }) || {}).name || ''
                : '';
            var xDataAttr = 'searchableSelect({' +
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
            var accountOptions = expenseAccounts.map(function(a) {
                return '<option value="' + a.id + '" ' + (data && data.expense_account_id == a.id ? 'selected' : '') + '>' + a.code + ' - ' + a.name + '</option>';
            }).join('');
            var tr = document.createElement('tr');
            tr.setAttribute('data-line-idx', idx);
            tr.innerHTML =
                '<td class="px-4 py-2" style="min-width: 220px;">' +
                    '<div x-data="' + escapeHtml(xDataAttr) + '" class="relative">' +
                        '<input type="hidden" name="lines[' + idx + '][product_id]" :value="selectedId" />' +
                        '<div class="flex">' +
                            '<input type="text" x-model="query" @input.debounce.200ms="filter()" @focus="if(query.length > 0) open = true" @keydown.down.prevent="moveHighlight(1)" @keydown.up.prevent="moveHighlight(-1)" @keydown.enter.prevent="confirmHighlight()" @keydown.escape="open = false" @keydown.tab="open = false" placeholder="Search products..." autocomplete="off" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-l-md shadow-sm text-sm" />' +
                            '<button type="button" @click="openAdvancedSearch()" class="px-2 bg-gray-50 border border-l-0 border-gray-300 rounded-r-md hover:bg-gray-100 focus:outline-none" title="Advanced Search">' +
                                '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="text" name="lines[' + idx + '][description]" value="' + (data ? (data.description || '') : '') + '" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm bg-gray-50" required />' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="number" name="lines[' + idx + '][quantity_received]" value="' + (data ? data.quantity_received : 1) + '" min="0.01" step="any" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" required />' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="number" name="lines[' + idx + '][unit_cost]" value="' + (data ? (data.unit_cost || 0) : 0) + '" min="0" step="0.01" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right bg-gray-50" onchange="updateTotals()" oninput="updateTotals()" required />' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<select name="lines[' + idx + '][expense_account_id]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">' +
                        '<option value="">Select</option>' +
                        accountOptions +
                    '</select>' +
                '</td>' +
                '<td class="px-4 py-2 text-right text-sm font-medium line-total">0.00</td>' +
                '<td class="px-4 py-2 text-center">' +
                    '<button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>' +
                '</td>';
            tbody.appendChild(tr);
            updateTotals();
        }

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateTotals();
        }

        if (selectedPo) {
            document.getElementById('vendor_id').value = selectedPo.vendor_id;
            selectedPo.lines.forEach(line => {
                addLine({
                    product_id: line.product_id,
                    description: line.description,
                    quantity_received: line.quantity - line.quantity_received,
                    unit_cost: line.unit_price,
                    expense_account_id: line.expense_account_id
                });
            });
        } else {
            addLine();
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
                var costInput = row.querySelector('[name*="[unit_cost]"]');
                if (costInput) costInput.value = parseFloat(item.purchase_price).toFixed(2);
            }
            if (item.expense_account_id) {
                var acctInput = row.querySelector('[name*="[expense_account_id]"]');
                if (acctInput) acctInput.value = item.expense_account_id;
            }
            updateTotals();
        });
    </script>
</x-app-layout>
