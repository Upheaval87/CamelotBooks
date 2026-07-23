<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Purchase Order') }} #{{ $order->po_number }}
            </h2>
            <a href="{{ route('accounting.purchase-orders.show', $order) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('accounting.purchase-orders.update', $order) }}">
                @csrf
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Order Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                            <select id="vendor_id" name="vendor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id', $order->vendor_id) == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="date" value="{{ __('Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', $order->date?->format('Y-m-d'))" required />
                        </div>
                        <div>
                            <x-input-label for="expected_delivery_date" value="{{ __('Expected Delivery') }}" />
                            <x-text-input id="expected_delivery_date" name="expected_delivery_date" type="date" class="mt-1 block w-full" :value="old('expected_delivery_date', $order->expected_delivery_date?->format('Y-m-d'))" />
                        </div>
                        <div>
                            <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                            <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">None</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $order->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="cost_center_id" value="{{ __('Cost Center') }}" />
                            <select id="cost_center_id" name="cost_center_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">None</option>
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}" {{ old('cost_center_id', $order->cost_center_id) == $cc->id ? 'selected' : '' }}>{{ $cc->code }} - {{ $cc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="memo" value="{{ __('Memo') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo', $order->memo)" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Line Items') }}</h3>
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
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body"></tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.purchase-orders.show', $order) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Update Purchase Order') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const products = @json($products);
        const expenseAccounts = @json($accounts);
        const existingLines = @json($order->lines);
        let lineIndex = 0;

        function updateTotals() {
            let total = 0;
            document.querySelectorAll('#lines-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
                const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
                row.querySelector('.line-total').textContent = (qty * price).toFixed(2);
                total += qty * price;
            });
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
                <td class="px-4 py-2"><select name="lines[${idx}][product_id]" class="block w-full border-gray-300 rounded-md shadow-sm text-sm"><option value="">None</option>${productOptions}</select></td>
                <td class="px-4 py-2"><input type="text" name="lines[${idx}][description]" value="${data ? data.description : ''}" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required /></td>
                <td class="px-4 py-2"><input type="number" name="lines[${idx}][quantity]" value="${data ? data.quantity : 1}" min="0.01" step="any" class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" required /></td>
                <td class="px-4 py-2"><input type="number" name="lines[${idx}][unit_price]" value="${data ? data.unit_price : 0}" min="0" step="0.01" class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" required /></td>
                <td class="px-4 py-2"><select name="lines[${idx}][expense_account_id]" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required><option value="">Select</option>${accountOptions}</select></td>
                <td class="px-4 py-2 text-right text-sm font-medium line-total">0.00</td>
                <td class="px-4 py-2 text-center"><button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900 text-sm">Remove</button></td>
            `;
            tbody.appendChild(tr);
        }

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateTotals();
        }

        existingLines.forEach(line => addLine(line));
        if (existingLines.length === 0) addLine();
        document.getElementById('add-line').addEventListener('click', () => addLine());
    </script>
</x-app-layout>
