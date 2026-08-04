<x-app-layout>
    <x-list-header title="{{ __('Record Vendor Payment') }}" />

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

            <div class="form-page">
                <div class="form-page-main">
            <form method="POST" action="{{ route('accounting.vendor-payments.store') }}">
                @csrf

                <div class="card p-6 mb-6">
                    <x-form.section number="01" :title="__('Payment Details')" />
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                            <x-scoped-search-field
                                name="vendor_id"
                                entity="vendor"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                                :value="old('vendor_id', $vendorId ?? '')"
                                :label="old('vendor_name', ($vendors->firstWhere('id', (int) old('vendor_id', $vendorId ?? ''))?->name ?? ''))"
                                placeholder="{{ __('Search vendors...') }}"
                                on-select="onVendorSelected"
                                required
                            />
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="payment_date" value="{{ __('Payment Date') }}" />
                            <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full" :value="old('payment_date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="amount" value="{{ __('Amount') }}" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount')" required oninput="autoAllocate()" />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="payment_method" value="{{ __('Payment Method') }}" />
                            <select id="payment_method" name="payment_method" class="input mt-1" required>
                                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="check" {{ old('payment_method') === 'check' ? 'selected' : '' }}>Check</option>
                                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="credit_card" {{ old('payment_method') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="bank_account_id" value="{{ __('Pay From') }}" />
                            <x-scoped-search-field
                                name="bank_account_id"
                                entity="bank-account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                                :value="old('bank_account_id')"
                                :label="old('bank_account_name', ($bankAccounts->firstWhere('id', (int) old('bank_account_id'))?->name ?? ''))"
                                placeholder="{{ __('Search bank accounts...') }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="Check # or reference" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                        <div class="col-span-4">
                            <x-input-label for="memo" value="{{ __('Description') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card p-6 mb-6">
                    <x-form.section number="02" :title="__('Allocate to Bills')" />
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Bill #</th>
                                    <th>Date</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Balance Due</th>
                                    <th class="text-right">Allocation</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="bills-body">
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500" id="no-bills">
                                        Select a vendor to load open bills.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card p-6 mb-6">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Total Payment:</span>
                                <span id="total-amount" class="text-gray-900">0.00</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Total Allocated:</span>
                                <span id="total-allocated" class="text-gray-900">0.00</span>
                            </div>
                            <div class="flex justify-between text-sm font-semibold border-t pt-2">
                                <span class="text-gray-800">Unallocated:</span>
                                <span id="unallocated" class="text-gray-900">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end mt-8 gap-3">
                    <x-button variant="ghost" href="{{ route('accounting.bills.index') }}">{{ __('Cancel') }}</x-button>
                    <x-primary-button type="submit">{{ __('Record Payment') }}</x-primary-button>
                </div>
            </form>
                </div>
                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Bill'), 'route' => route('accounting.bills.create'), 'icon' => 'document'],
                        ['title' => __('New Vendor'), 'route' => route('accounting.vendors.create'), 'icon' => 'person'],
                    ]],
                    ['label' => __('View'), 'links' => [
                    ]],
                ]" />
            </div>
        </div>
    </div>

    <script>
        const openBillsByVendor = @json($openBillsByVendor ?? []);

        function loadBills() {
            const vendorId = document.querySelector('input[name="vendor_id"]').value;
            const tbody = document.getElementById('bills-body');
            const bills = openBillsByVendor[vendorId] || [];
            if (!bills.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-ink-soft">No open bills for this vendor.</td></tr>';
                return;
            }
            tbody.innerHTML = bills.map(bill => `
                <tr data-bill-id="${bill.id}" data-balance="${bill.balance_due}">
                    <td>${bill.bill_number}</td>
                    <td class="text-ink-soft">${bill.bill_date ?? '—'}</td>
                    <td class="numeric">${parseFloat(bill.total).toFixed(2)}</td>
                    <td class="numeric">${parseFloat(bill.balance_due).toFixed(2)}</td>
                    <td class="numeric">
                        <input type="number" name="allocations[${bill.id}]" class="allocation-input block w-32 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" min="0" max="${bill.balance_due}" step="0.01" value="0" oninput="updateAllocationTotals()" />
                    </td>
                </tr>
            `).join('');
            autoAllocate();
        }

        function onVendorSelected() {
            loadBills();
        }

        function autoAllocate() {
            const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
            let remaining = totalAmount;
            document.querySelectorAll('#bills-body tr').forEach(row => {
                const input = row.querySelector('.allocation-input');
                if (!input) return;
                const balance = parseFloat(row.dataset.balance) || 0;
                const alloc = Math.min(remaining, balance);
                input.value = alloc > 0 ? alloc.toFixed(2) : '0';
                remaining -= alloc;
            });
            updateAllocationTotals();
        }

        function updateAllocationTotals() {
            const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
            let totalAllocated = 0;
            document.querySelectorAll('.allocation-input').forEach(input => {
                totalAllocated += parseFloat(input.value) || 0;
            });
            document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
            document.getElementById('total-allocated').textContent = totalAllocated.toFixed(2);
            document.getElementById('unallocated').textContent = (totalAmount - totalAllocated).toFixed(2);
        }

        document.getElementById('amount').addEventListener('input', updateAllocationTotals);
    </script>
</x-app-layout>
