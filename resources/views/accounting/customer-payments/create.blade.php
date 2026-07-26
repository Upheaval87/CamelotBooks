<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Record Customer Payment') }}
            </h2>
            <a href="{{ route('accounting.invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Invoices') }}
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

            <form method="POST" action="{{ route('accounting.customer-payments.store') }}">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payment Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="customer_id" value="{{ __('Customer') }}" />
                            <div x-data="customerSearch('{{ old('customer_id') }}', '{{ old('customer_name', '') }}', true)" class="relative">
                                <input type="hidden" id="customer_id" name="customer_id" :value="selectedId" />
                                <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query) open=true" @click.away="open=false" placeholder="Search customers..." autocomplete="off" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <div x-show="open && results.length > 0" x-cloak class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                    <template x-for="c in results" :key="c.id">
                                        <div @click="select(c)" class="px-3 py-2 cursor-pointer hover:bg-indigo-50 text-sm" x-text="c.name + (c.email ? ' (' + c.email + ')' : '')"></div>
                                    </template>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
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
                            <select id="payment_method" name="payment_method" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="check" {{ old('payment_method') === 'check' ? 'selected' : '' }}>Check</option>
                                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="credit_card" {{ old('payment_method') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="bank_account_id" value="{{ __('Deposit To') }}" />
                            <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                        {{ $bankAccount->name }} ({{ format_money($bankAccount->current_balance) }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="Check # or reference" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="memo" value="{{ __('Memo') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Allocate to Invoices') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance Due</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Allocation</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="invoices-body">
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500" id="no-invoices">
                                        Select a customer to load open invoices.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
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

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Record Payment') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function customerSearch(selectedId, selectedName, triggerLoadInvoices) {
            return {
                query: selectedName || '', selectedId: selectedId || '', results: [], open: false,
                async search() {
                    if (this.query.length < 1) { this.results = []; this.open = false; return; }
                    const r = await fetch('{{ route("accounting.customers.search") }}?q=' + encodeURIComponent(this.query));
                    this.results = await r.json(); this.open = true;
                },
                select(c) {
                    this.selectedId = c.id; this.query = c.name; this.open = false;
                    if (triggerLoadInvoices) loadInvoices();
                }
            }
        }

        const openInvoicesByCustomer = @json($openInvoicesByCustomer ?? []);

        function loadInvoices() {
            const customerId = document.getElementById('customer_id').value;
            const tbody = document.getElementById('invoices-body');
            const invoices = openInvoicesByCustomer[customerId] || [];
            if (!invoices.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No open invoices for this customer.</td></tr>';
                return;
            }
            tbody.innerHTML = invoices.map(inv => `
                <tr data-invoice-id="${inv.id}" data-balance="${inv.balance_due}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${inv.invoice_number}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${inv.invoice_date ?? '—'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${parseFloat(inv.total).toFixed(2)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">${parseFloat(inv.balance_due).toFixed(2)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                        <input type="number" name="allocations[${inv.id}]" class="allocation-input block w-32 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" min="0" max="${inv.balance_due}" step="0.01" value="0" oninput="updateAllocationTotals()" />
                    </td>
                </tr>
            `).join('');
            autoAllocate();
        }

        function autoAllocate() {
            const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
            let remaining = totalAmount;
            document.querySelectorAll('#invoices-body tr').forEach(row => {
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
