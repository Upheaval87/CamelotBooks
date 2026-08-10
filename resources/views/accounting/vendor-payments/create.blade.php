<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $selectedVendor = old('vendor_id') ? $vendors->firstWhere('id', (int) old('vendor_id')) : ($preselectVendor ?? null);
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('Record Vendor Payment') }}</h1>
                    <div class="sub">{{ __('Pay a vendor and apply the payment to open bills.') }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.vendors.index') }}" class="btn ghost sm">{{ __('Cancel') }}</a>
                    <button type="submit" form="vp-form" class="btn cta">{{ __('Record Payment') }}</button>
                </div>
            </div>

            <form method="POST" action="{{ route('accounting.vendor-payments.store') }}" id="vp-form" novalidate>
                @csrf

                <x-input-error :messages="$errors->get('error')" class="mb-4" />

                <div class="shell">
                    <div class="flex flex-col gap-5 min-w-0">

                        {{-- payment details --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg></span>
                                <h2>{{ __('Payment Details') }}</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="g4">
                                <div class="field sp2">
                                    <label for="vendor_id">Vendor <span class="req">*</span></label>
                                    <x-scoped-search-field
                                        name="vendor_id"
                                        entity="vendor"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                                        :value="old('vendor_id', $vendorId ?? '')"
                                        :label="old('vendor_name', $selectedVendor?->name ?? '')"
                                        placeholder="Search vendors..."
                                        on-select="onVendorSelected"
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="payment_date">Payment Date <span class="req">*</span></label>
                                    <input id="payment_date" name="payment_date" type="date" class="input" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required />
                                    <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="amount">Amount <span class="req">*</span></label>
                                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="input" value="{{ old('amount') }}" placeholder="0.00" required oninput="autoAllocate()" />
                                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="payment_method">Payment Method</label>
                                    <select id="payment_method" name="payment_method" class="input">
                                        <option value="">— Select —</option>
                                        @foreach(['cash', 'check', 'bank_transfer', 'credit_card', 'other'] as $method)
                                            <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                                </div>
                                <div class="field sp2">
                                    <label for="bank_account_id">Pay From <span class="req">*</span></label>
                                    <x-scoped-search-field
                                        name="bank_account_id"
                                        entity="bank-account"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                                        :value="old('bank_account_id')"
                                        :label="old('bank_account_name', ($bankAccounts->firstWhere('id', (int) old('bank_account_id'))?->name ?? ''))"
                                        placeholder="Search bank accounts..."
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="reference">Reference</label>
                                    <input id="reference" name="reference" type="text" class="input" value="{{ old('reference') }}" placeholder="Check # or reference" />
                                    <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="memo">Description</label>
                                    <input id="memo" name="memo" type="text" class="input" value="{{ old('memo') }}" placeholder="Optional memo" />
                                    <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        {{-- allocations --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/></svg></span>
                                <h2>{{ __('Apply to Bills') }}</h2>
                                <span class="rule"></span>
                            </div>

                            <div class="li-wrap" style="margin-top:0">
                                <table id="alloc-table" class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Bill #') }}</th>
                                            <th>{{ __('Date') }}</th>
                                            <th class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                            <th class="num">{{ __('Balance Due') }} ({{ $cs }})</th>
                                            <th class="num" style="width:14rem">{{ __('Allocation') }} ({{ $cs }})</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200" id="alloc-body">
                                        <tr id="no-alloc-row">
                                            <td colspan="5" style="padding:28px 16px;text-align:center;color:var(--muted,#5f7476)">
                                                {{ __('Select a vendor to load open bills.') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <x-input-error :messages="$errors->get('allocations')" class="mt-2" />

                            <div class="li-totals" style="margin-top:16px">
                                <div class="box" style="max-width:22rem;margin-left:auto">
                                    <div class="trow"><span>{{ __('Total Payment') }}</span><span class="v" id="total-amount">0.00</span></div>
                                    <div class="trow"><span>{{ __('Total Allocated') }}</span><span class="v" id="total-allocated">0.00</span></div>
                                    <div class="trow total"><span>{{ __('Unallocated') }}</span><span class="v" id="unallocated">0.00</span></div>
                                </div>
                            </div>
                        </section>
                    </div>

                    {{-- rail --}}
                    <aside class="railsum">
                        <section class="card">
                            <div class="rail-sec">
                                <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7.5h8M8.5 12h.01M12 12h.01"/></svg></span><h2>{{ __('Summary') }}</h2></div>
                                <div style="margin-top:8px">
                                    <div class="srow"><span class="l">{{ __('Payment Amount') }}</span><span class="v" id="rail-amount">0.00</span></div>
                                    <div class="srow"><span class="l">{{ __('Allocated') }}</span><span class="v" id="rail-allocated">0.00</span></div>
                                    <div class="srow strong"><span class="l">{{ __('Unallocated') }}</span><span class="v" id="rail-unallocated">0.00</span></div>
                                </div>
                                <div class="gt"><span class="l">{{ __('Total Payment') }}</span><span class="v" id="rail-gt">0.00</span></div>
                            </div>
                            <div class="rail-sec">
                                <div class="sec-head"><span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/></svg></span><h2>{{ __('Quick Nav') }}</h2></div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.bills.create') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg></span>{{ __('New Bill') }}</a>
                                    <a href="{{ route('accounting.vendors.create') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5"/></svg></span>{{ __('New Vendor') }}</a>
                                    <a href="{{ route('accounting.vendors.index') }}" class="vitem"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7 7-7M3 12h18"/></svg></span>{{ __('Vendors List') }}</a>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <script>
        const openBillsByVendor = @json($openBillsByVendor);

        function onVendorSelected() {
            loadBills();
        }

        function loadBills() {
            const vendorId = document.querySelector('input[name="vendor_id"]').value;
            const tbody = document.getElementById('alloc-body');
            const bills = openBillsByVendor[vendorId] || [];
            if (!bills.length) {
                tbody.innerHTML = '<tr><td colspan="5" style="padding:28px 16px;text-align:center;color:var(--muted,#5f7476)">No open bills for this vendor.</td></tr>';
                updateAllocationTotals();
                return;
            }
            tbody.innerHTML = bills.map((bill, i) => `
                <tr data-idx="${i}" data-balance="${bill.balance_due}">
                    <td class="mono">${bill.bill_number}</td>
                    <td>${bill.bill_date ?? '—'}</td>
                    <td class="numr">${Number(bill.amount).toFixed(2)}</td>
                    <td class="numr">${Number(bill.balance_due).toFixed(2)}</td>
                    <td style="text-align:right">
                        <input type="hidden" name="allocations[${i}][bill_id]" value="${bill.id}" />
                        <input type="number" name="allocations[${i}][amount]" class="input alloc-input" min="0" max="${bill.balance_due}" step="0.01" value="0" style="text-align:right" oninput="updateAllocationTotals()" />
                    </td>
                </tr>
            `).join('');
            autoAllocate();
        }

        function autoAllocate() {
            const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
            let remaining = totalAmount;
            document.querySelectorAll('#alloc-body tr').forEach(row => {
                const input = row.querySelector('.alloc-input');
                if (!input) return;
                const balance = parseFloat(row.dataset.balance) || 0;
                const alloc = Math.min(Math.max(remaining, 0), balance);
                input.value = alloc > 0 ? alloc.toFixed(2) : '0';
                remaining -= alloc;
            });
            updateAllocationTotals();
        }

        function updateAllocationTotals() {
            const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
            let totalAllocated = 0;
            document.querySelectorAll('.alloc-input').forEach(input => {
                totalAllocated += parseFloat(input.value) || 0;
            });
            const unallocated = totalAmount - totalAllocated;
            document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
            document.getElementById('total-allocated').textContent = totalAllocated.toFixed(2);
            document.getElementById('unallocated').textContent = unallocated.toFixed(2);
            document.getElementById('rail-amount').textContent = totalAmount.toFixed(2);
            document.getElementById('rail-allocated').textContent = totalAllocated.toFixed(2);
            document.getElementById('rail-unallocated').textContent = unallocated.toFixed(2);
            document.getElementById('rail-gt').textContent = totalAmount.toFixed(2);
        }

        document.getElementById('amount').addEventListener('input', updateAllocationTotals);

        @if($vendorId)
        loadBills();
        @endif
    </script>
</x-app-layout>
