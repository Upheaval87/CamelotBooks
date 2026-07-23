<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Goods Received Note') }}
            </h2>
            <a href="{{ route('accounting.goods-received-notes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('accounting.goods-received-notes.store') }}" id="grn-form">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('GRN Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                            <select id="vendor_id" name="vendor_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="purchase_order_id" value="{{ __('Purchase Order (Optional)') }}" />
                            <select id="purchase_order_id" name="purchase_order_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Received Items') }}</h3>
                        <button type="button" id="add-line" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Add Line') }}
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="lines-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Received</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body"></tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-end">
                        <div class="w-48 space-y-2">
                            <div class="flex justify-between text-sm font-semibold border-t pt-2">
                                <span class="text-gray-800">Total:</span>
                                <span id="grand-total" class="text-gray-900">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.goods-received-notes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Create GRN') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const products = @json($products);
        const expenseAccounts = @json($accounts);
        const selectedPo = @json($selectedPo);
        let lineIndex = 0;

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

        function addLine(data = null) {
            const tbody = document.getElementById('lines-body');
            const idx = lineIndex++;
            const productOptions = products.map(p =>
                `<option value="${p.id}" ${data && data.product_id == p.id ? 'selected' : ''}>${p.name}</option>`
            ).join('');
            const accountOptions = expenseAccounts.map(a =>
                `<option value="${a.id}" ${data && data.expense_account_id == a.id ? 'selected' : ''}>${a.code} - ${a.name}</option>`
            ).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-2">
                    <select name="lines[${idx}][product_id]" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">None</option>
                        ${productOptions}
                    </select>
                </td>
                <td class="px-4 py-2">
                    <input type="text" name="lines[${idx}][description]" value="${data ? data.description : ''}" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity_received]" value="${data ? data.quantity_received : 1}" min="0.01" step="any" class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" required />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][unit_cost]" value="${data ? data.unit_cost : 0}" min="0" step="0.01" class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" required />
                </td>
                <td class="px-4 py-2">
                    <select name="lines[${idx}][expense_account_id]" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Select</option>
                        ${accountOptions}
                    </select>
                </td>
                <td class="px-4 py-2 text-right text-sm font-medium line-total">0.00</td>
                <td class="px-4 py-2 text-center">
                    <button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900 text-sm">Remove</button>
                </td>
            `;
            tbody.appendChild(tr);
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
    </script>
</x-app-layout>
