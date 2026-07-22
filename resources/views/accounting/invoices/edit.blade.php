<x-app-layout>
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
                            <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id', $invoice->customer_id) == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
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
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Discount %</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax Rate %</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Line Total</th>
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
        const products = @json($products);
        const incomeAccounts = @json($incomeAccounts);
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
            const productOptions = products.map(p =>
                `<option value="${p.id}" ${data && data.product_id == p.id ? 'selected' : ''} data-description="${p.description || ''}" data-unit_price="${p.unit_price || 0}" data-tax_rate="${p.tax_rate || 0}" data-income_account_id="${p.income_account_id || ''}">${p.name}</option>`
            ).join('');
            const accountOptions = incomeAccounts.map(a =>
                `<option value="${a.id}" ${data && data.income_account_id == a.id ? 'selected' : ''}>${a.code} - ${a.name}</option>`
            ).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-2">
                    <select name="lines[${idx}][product_id]" class="product-select block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" onchange="fillProduct(this)">
                        <option value="">Select Product</option>
                        ${productOptions}
                    </select>
                </td>
                <td class="px-4 py-2">
                    <input type="text" name="lines[${idx}][description]" value="${data ? (data.description || '') : ''}" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity]" value="${data ? data.quantity : 1}" min="0" step="any" class="line-qty block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][unit_price]" value="${data ? data.unit_price : 0}" min="0" step="0.01" class="line-price block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][discount]" value="${data ? data.discount : 0}" min="0" max="100" step="0.01" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][tax_rate]" value="${data ? data.tax_rate : 0}" min="0" max="100" step="0.01" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <select name="lines[${idx}][income_account_id]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">Select Account</option>
                        ${accountOptions}
                    </select>
                </td>
                <td class="px-4 py-2 text-right text-sm font-medium line-total">0.00</td>
                <td class="px-4 py-2 text-center">
                    <button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900 text-sm">Remove</button>
                </td>
            `;
            tbody.appendChild(tr);
            updateTotals();
        }

        function fillProduct(select) {
            const option = select.options[select.selectedIndex];
            const row = select.closest('tr');
            if (option.value) {
                row.querySelector('[name*="[description"]').value = option.dataset.description || '';
                row.querySelector('[name*="[unit_price"]').value = option.dataset.unit_price || 0;
                row.querySelector('[name*="[tax_rate"]').value = option.dataset.tax_rate || 0;
                const accSelect = row.querySelector('[name*="[income_account_id"]');
                if (option.dataset.income_account_id) {
                    accSelect.value = option.dataset.income_account_id;
                }
            }
            updateTotals();
        }

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateTotals();
        }

        document.getElementById('add-line').addEventListener('click', () => addLine());
        existingLines.forEach(line => addLine(line));
    </script>
</x-app-layout>
