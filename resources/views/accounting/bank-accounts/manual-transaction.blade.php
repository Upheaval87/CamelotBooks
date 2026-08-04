<x-app-layout>
    <x-list-header title="{{ __('Manual Bank Transaction') }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.bank-accounts.index') }}">{{ __('Back to Accounts') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.bank-accounts.store-manual', $bankAccount->id) }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="bank_account_id" value="{{ __('Bank Account') }}" />
                            <input type="text" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-gray-50" value="{{ $bankAccount->name }}" disabled />
                            <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}" />
                        </div>

                        <div x-data="{ type: '{{ old('type', 'fee') }}' }">
                            <x-input-label for="type" value="{{ __('Transaction Type') }}" />
                            <select id="type" name="type" x-model="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="fee">Bank Fee</option>
                                <option value="withdrawal">Withdrawal / Other Expense</option>
                                <option value="deposit">Deposit / Other Income</option>
                                <option value="interest">Interest Earned</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />

                            <div class="mt-4">
                                <x-input-label for="date" value="{{ __('Date') }}" />
                                <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('date')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="amount" value="{{ __('Amount') }}" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>

                            <div class="mt-4" x-show="type === 'fee' || type === 'withdrawal'">
                                <x-input-label for="debit_account_id" value="{{ __('Expense Account (Debit)') }}" />
                                <p class="text-xs text-gray-500 mb-1">The expense/asset account to charge this transaction to.</p>
                                <x-scoped-search-field
                                    name="debit_account_id"
                                    entity="account"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                    :value="old('debit_account_id')"
                                    :label="old('debit_account_id') ? (($accounts->firstWhere('id', (int) old('debit_account_id'))) ? $accounts->firstWhere('id', (int) old('debit_account_id'))->code . ' - ' . $accounts->firstWhere('id', (int) old('debit_account_id'))->name : '') : ''"
                                    placeholder="{{ __('Select Account') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('debit_account_id')" class="mt-2" />
                            </div>

                            <div class="mt-4" x-show="type === 'deposit' || type === 'interest'">
                                <x-input-label for="credit_account_id" value="{{ __('Income/Credit Account') }}" />
                                <p class="text-xs text-gray-500 mb-1">The income/equity account to credit for this deposit.</p>
                                <x-scoped-search-field
                                    name="credit_account_id"
                                    entity="account"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                    :value="old('credit_account_id')"
                                    :label="old('credit_account_id') ? (($accounts->firstWhere('id', (int) old('credit_account_id'))) ? $accounts->firstWhere('id', (int) old('credit_account_id'))->code . ' - ' . $accounts->firstWhere('id', (int) old('credit_account_id'))->name : '') : ''"
                                    placeholder="{{ __('Select Account') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('credit_account_id')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" placeholder="Transaction description" required />
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="Optional reference" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('accounting.bank-accounts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit">{{ __('Save Transaction') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
