<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Bill') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.bills.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to Bills') }}
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

            <form method="POST" action="{{ route('accounting.bills.store') }}" id="bill-form">
                @csrf

                <div class="card p-6 mb-6">
                    <div class="form-section-label">1 · BILL DETAILS</div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                            <select id="vendor_id" name="vendor_id" class="input mt-1" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id', $selectedVendorId ?? '') == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="bill_number" value="{{ __('Bill Number (Vendor Ref)') }}" />
                            <x-text-input id="bill_number" name="bill_number" type="text" class="mt-1 block w-full" :value="old('bill_number')" required />
                            <x-input-error :messages="$errors->get('bill_number')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="bill_date" value="{{ __('Bill Date') }}" />
                            <x-text-input id="bill_date" name="bill_date" type="date" class="mt-1 block w-full" :value="old('bill_date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('bill_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="{{ __('Due Date') }}" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date')" required />
                            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="memo" value="{{ __('Memo') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="form-section-label">2 · LINE ITEMS</div>
                        <button type="button" id="add-line" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Add Line') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="lines-table">
                            <thead>
                                <tr>
                                    <th>Expense Account</th>
                                    <th>Description</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price ({{ $cs }})</th>
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

                <div class="flex justify-end gap-3">
                    <x-button variant="ghost" href="{{ route('accounting.bills.index') }}">{{ __('Cancel') }}</x-button>
                    <x-primary-button type="submit">{{ __('Create Bill') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const expenseAccounts = @json($expenseAccounts);
        const costCenters = @json($costCenters);
        let lineIndex = 0;

        function updateTotals() {
            let subtotal = 0;
            let totalTax = 0;
            document.querySelectorAll('#lines-body tr').forEach(row => {
                const qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
                const price = parseFloat(row.querySelector('[name*="[unit_price]"]').value) || 0;
                const taxRate = parseFloat(row.querySelector('[name*="[tax_rate]"]').value) || 0;
                const lineSubtotal = qty * price;
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
            const accountOptions = expenseAccounts.map(a =>
                `<option value="${a.id}">${a.code} - ${a.name}</option>`
            ).join('');
            const costCenterOptions = costCenters.map(c =>
                `<option value="${c.id}">${c.code} - ${c.name}</option>`
            ).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-2">
                    <select name="lines[${idx}][expense_account_id]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                        <option value="">Select Account</option>
                        ${accountOptions}
                    </select>
                </td>
                <td class="px-4 py-2">
                    <input type="text" name="lines[${idx}][description]" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm bg-gray-50" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity]" value="1" min="0" step="any" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][unit_price]" value="0" min="0" step="0.01" readonly class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right bg-gray-50" />
                </td>
                <td class="px-4 py-2"><input type="hidden" name="lines[${idx}][tax_rate]" value="0" />
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

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateTotals();
        }

        document.getElementById('add-line').addEventListener('click', addLine);
        addLine();
    </script>
</x-app-layout>
