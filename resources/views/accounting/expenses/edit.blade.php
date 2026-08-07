<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Edit Expense') }} {{ $expense->expense_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <form method="POST" action="{{ route('accounting.expenses.update', $expense) }}" id="expense-form">
                        @csrf
                        @method('PUT')

                        <div class="card p-6 mb-6">
                            <x-form.section number="01" :title="__('Expense Details')" />
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="vendor_id" value="{{ __('Vendor (optional)') }}" />
                                    <x-scoped-search-field
                                        name="vendor_id"
                                        entity="vendor"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                                        :value="old('vendor_id', $expense->vendor_id)"
                                        :label="old('vendor_name', $expense->vendor?->name ?? '')"
                                        placeholder="{{ __('Search vendors...') }}"
                                    />
                                </div>
                                <div>
                                    <x-input-label for="expense_date" value="{{ __('Expense Date') }}" />
                                    <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1 block w-full" :value="old('expense_date', $expense->expense_date?->format('Y-m-d'))" required />
                                </div>
                                <div>
                                    <x-input-label for="bank_account_id" value="{{ __('Paid From Account') }}" />
                                    <x-scoped-search-field
                                        name="bank_account_id"
                                        entity="bank-account"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                                        :value="old('bank_account_id', $expense->bank_account_id)"
                                        :label="old('bank_account_name', ($bankAccounts->firstWhere('id', (int) $expense->bank_account_id)?->name ?? ''))"
                                        placeholder="{{ __('Search bank accounts...') }}"
                                    />
                                </div>
                                <div>
                                    <x-input-label for="reference" value="{{ __('Reference') }}" />
                                    <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference', $expense->reference)" />
                                </div>
                                <div class="col-span-4">
                                    <x-input-label for="memo" value="{{ __('Description') }}" />
                                    <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo', $expense->memo)" />
                                </div>
                            </div>
                        </div>

                        <div class="card p-6 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <x-form.section number="02" :title="__('Line Items')" />
                                <button type="button" id="add-line" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
                            <x-button variant="ghost" href="{{ route('accounting.expenses.show', $expense) }}">{{ __('Cancel') }}</x-button>
                            <x-primary-button type="submit">{{ __('Update Expense') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const expenseAccounts = @json($expenseAccounts);
        const costCenters = @json($costCenters);
        const existingLines = @json($expense->lines);
        const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
        const COST_CENTER_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'cost-center']));
        let lineIndex = 0;

        function expenseAccountLabel(id) {
            const a = expenseAccounts.find(x => x.id == id);
            return a ? a.code + ' - ' + a.name : '';
        }

        function costCenterLabel(id) {
            const c = costCenters.find(x => x.id == id);
            return c ? c.code + ' - ' + c.name : '';
        }

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

        function addLine(data) {
            const tbody = document.getElementById('lines-body');
            const idx = lineIndex++;
            const accountId = data ? data.expense_account_id : '';
            const costCenterId = data ? data.cost_center_id : '';
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-2">
                    ${scopedSearchFieldHtml({
                        name: 'lines[${idx}][expense_account_id]',
                        entity: 'account',
                        searchUrl: ACCOUNT_SEARCH_URL,
                        value: accountId,
                        label: accountId ? expenseAccountLabel(accountId) : '',
                        placeholder: 'Search accounts...',
                        required: true,
                    })}
                </td>
                <td class="px-4 py-2">
                    <input type="text" name="lines[${idx}][description]" value="${data ? data.description : ''}" readonly class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm bg-gray-50" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][quantity]" value="${data ? data.quantity : 1}" min="0" step="any" class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right" onchange="updateTotals()" oninput="updateTotals()" />
                </td>
                <td class="px-4 py-2">
                    <input type="number" name="lines[${idx}][unit_price]" value="${data ? data.unit_price : 0}" min="0" step="0.01" readonly class="block w-full border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm text-right bg-gray-50" />
                </td>
                <td class="px-4 py-2"><input type="hidden" name="lines[${idx}][tax_rate]" value="${data ? data.tax_rate : 0}" />
                    ${scopedSearchFieldHtml({
                        name: 'lines[${idx}][cost_center_id]',
                        entity: 'cost-center',
                        searchUrl: COST_CENTER_SEARCH_URL,
                        value: costCenterId,
                        label: costCenterId ? costCenterLabel(costCenterId) : '',
                        placeholder: 'None',
                    })}
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

        document.getElementById('add-line').addEventListener('click', function() { addLine(); });

        if (existingLines.length > 0) {
            existingLines.forEach(function(line) {
                addLine(line);
            });
        } else {
            addLine();
        }

        updateTotals();
    </script>
</x-app-layout>
