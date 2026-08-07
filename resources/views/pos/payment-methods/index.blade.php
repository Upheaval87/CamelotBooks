<x-app-layout>
    <x-list-header title="{{ __('POS Payment Methods') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 card p-6">
                <div class="form-section-label">1 · Add Payment Method</div>
                <form method="POST" action="{{ route('pos.payment-methods.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="name" value="{{ __('Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="e.g. Cash, Visa, Airtel Money" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="type" value="{{ __('Type') }}" />
                            <select id="type" name="type" class="input mt-1" required>
                                <option value="cash" {{ old('type') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="card" {{ old('type') === 'card' ? 'selected' : '' }}>Card</option>
                                <option value="mobile_money" {{ old('type') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="clearing_account_id" value="{{ __('Clearing Account') }}" />
                            <x-scoped-search-field
                                name="clearing_account_id"
                                entity="account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                :value="old('clearing_account_id')"
                                :label="old('clearing_account_id') ? (($clearingAccounts->firstWhere('id', (int) old('clearing_account_id')) ? $clearingAccounts->firstWhere('id', (int) old('clearing_account_id'))->code . ' - ' . $clearingAccounts->firstWhere('id', (int) old('clearing_account_id'))->name : '')) : ''"
                                placeholder="{{ __('None') }}"
                            />
                            <x-input-error :messages="$errors->get('clearing_account_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="settlement_bank_account_id" value="{{ __('Settlement Bank Account') }}" />
                            <x-scoped-search-field
                                name="settlement_bank_account_id"
                                entity="account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                :value="old('settlement_bank_account_id')"
                                :label="old('settlement_bank_account_id') ? (($accounts->firstWhere('id', (int) old('settlement_bank_account_id')) ? $accounts->firstWhere('id', (int) old('settlement_bank_account_id'))->code . ' - ' . $accounts->firstWhere('id', (int) old('settlement_bank_account_id'))->name : '')) : ''"
                                placeholder="{{ __('None') }}"
                            />
                            <x-input-error :messages="$errors->get('settlement_bank_account_id')" class="mt-2" />
                        </div>
                        <div class="flex items-center gap-2 mt-6">
                            <input type="checkbox" id="requires_reference" name="requires_reference" value="1" {{ old('requires_reference') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-gold-700 shadow-sm focus:ring-gold-500" />

                            <x-input-label for="requires_reference" :value="__('Requires Reference Number')" class="mb-0" />
                        </div>
                    </div>
                    <div>
                        <x-primary-button type="submit">{{ __('Add') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Clearing Account</th>
                                <th>Settlement Account</th>
                                <th class="text-center">Ref Required</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentMethods as $method)
                                <tr class="{{ $method->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                    <td>{{ $method->name }}</td>
                                    <td>
                                        <span class="status-pill {{ $method->type === 'cash' ? 'positive' : ($method->type === 'card' ? 'positive' : 'positive') }}">
                                            {{ ucwords(str_replace('_', ' ', $method->type)) }}
                                        </span>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $method->clearingAccount ? $method->clearingAccount->code . ' - ' . $method->clearingAccount->name : '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $method->settlementBankAccount ? $method->settlementBankAccount->code . ' - ' . $method->settlementBankAccount->name : '—' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $method->requires_reference ? 'Yes' : 'No' }}
                                    </td>
                                    <td class="text-center">
                                        @if($method->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill negative">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('pos.payment-methods.toggle', $method) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-{{ $method->is_active ? 'red' : 'green' }}-600 hover:text-{{ $method->is_active ? 'red' : 'green' }}-900">
                                                {{ $method->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-ink-soft text-center">No payment methods found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
