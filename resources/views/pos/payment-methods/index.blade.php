<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>Payment Methods</h1>
                <p class="pos-sub">Cash · card · mobile money · bank transfer · customer credit</p>
            </div>
        </div>

        <div class="pos-shell">
            <div>
                {{-- Section 1: Add Payment Method --}}
                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">1 · Add Payment Method</span>
                    </div>
                    <div class="pos-pad">
                        <form method="POST" action="{{ route('pos.payment-methods.store') }}">
                            @csrf
                            <div class="pos-g3" style="margin-bottom:12px">
                                <div class="pos-f">
                                    <label>Name <span style="color:var(--pos-red)">*</span></label>
                                    <input class="pos-in" name="name" value="{{ old('name') }}" required placeholder="e.g. Cash, Visa, Airtel Money">
                                    @error('name')<div style="font-size:11px;color:var(--pos-red);margin-top:4px">{{ $message }}</div>@enderror
                                </div>
                                <div class="pos-f">
                                    <label>Type <span style="color:var(--pos-red)">*</span></label>
                                    <select name="type" class="pos-in" required>
                                        <option value="">— Select —</option>
                                        <option value="cash" {{ old('type') === 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="card" {{ old('type') === 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="mobile_money" {{ old('type') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                        <option value="bank_transfer" {{ old('type') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Customer Credit</option>
                                    </select>
                                    @error('type')<div style="font-size:11px;color:var(--pos-red);margin-top:4px">{{ $message }}</div>@enderror
                                </div>
                                <div class="pos-f">
                                    <label>Fee %</label>
                                    <input class="pos-in" type="number" name="fee_percent" value="{{ old('fee_percent', '0') }}" min="0" max="100" step="0.01">
                                    @error('fee_percent')<div style="font-size:11px;color:var(--pos-red);margin-top:4px">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="pos-g2" style="margin-bottom:12px">
                                <div class="pos-f">
                                    <label>Clearing Account</label>
                                    <x-scoped-search-field
                                        name="clearing_account_id"
                                        entity="account"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                        :value="old('clearing_account_id')"
                                        :label="old('clearing_account_id') ? (($clearingAccounts->firstWhere('id', (int) old('clearing_account_id')) ? $clearingAccounts->firstWhere('id', (int) old('clearing_account_id'))->code . ' - ' . $clearingAccounts->firstWhere('id', (int) old('clearing_account_id'))->name : '')) : ''"
                                        placeholder="{{ __('None') }}"
                                    />
                                </div>
                                <div class="pos-f">
                                    <label>Settlement Bank Account</label>
                                    <x-scoped-search-field
                                        name="settlement_bank_account_id"
                                        entity="account"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                        :value="old('settlement_bank_account_id')"
                                        :label="old('settlement_bank_account_id') ? (($accounts->firstWhere('id', (int) old('settlement_bank_account_id')) ? $accounts->firstWhere('id', (int) old('settlement_bank_account_id'))->code . ' - ' . $accounts->firstWhere('id', (int) old('settlement_bank_account_id'))->name : '')) : ''"
                                        placeholder="{{ __('None') }}"
                                    />
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--pos-ink)">
                                    <input type="checkbox" name="requires_reference" value="1" {{ old('requires_reference') ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--pos-sec)">
                                    Requires Reference Number
                                </label>
                            </div>
                            <button type="submit" class="pos-btn pos-btn-cta">Add Payment Method</button>
                        </form>
                    </div>
                </div>

                {{-- Section 2: Methods Table --}}
                <div class="pos-card">
                    <div class="pos-card-h">
                        <span class="pos-step">2 · Active Methods</span>
                    </div>
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Clearing Account</th>
                                    <th class="num">Fee %</th>
                                    <th>Settlement Account</th>
                                    <th class="num">Ref Required</th>
                                    <th>Status</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentMethods as $method)
                                    <tr>
                                        <td class="pos-bold">{{ $method->name }}</td>
                                        <td><span class="pos-tchip pos-tchip-pay">{{ ucwords(str_replace('_', ' ', $method->type)) }}</span></td>
                                        <td class="pos-em">{{ $method->clearingAccount ? $method->clearingAccount->code . ' · ' . $method->clearingAccount->name : '—' }}</td>
                                        <td class="num">{{ $method->fee_percent > 0 ? $method->fee_percent . '%' : '0' }}</td>
                                        <td class="pos-em">{{ $method->settlementBankAccount ? $method->settlementBankAccount->code . ' · ' . $method->settlementBankAccount->name : '—' }}</td>
                                        <td class="num">{{ $method->requires_reference ? 'Yes' : 'No' }}</td>
                                        <td>
                                            @if($method->is_active)
                                                <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Active</span>
                                            @else
                                                <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                            @endif
                                        </td>
                                        <td class="num">
                                            <div class="pos-row-act">
                                                <form method="POST" action="{{ route('pos.payment-methods.toggle', $method) }}" style="display:inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="pos-btn pos-btn-xs {{ $method->is_active ? 'pos-btn-danger-o' : 'pos-btn-sec' }}">
                                                        {{ $method->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="pos-empty">
                                                <h3>No payment methods</h3>
                                                <p>Add a payment method to accept different payment types.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.settings.index') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.32 9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        Settings
                    </a>
                    <a href="{{ route('pos.terminals.index') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Registers
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
