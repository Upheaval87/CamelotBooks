<x-app-layout>
    <x-list-header title="{{ __('Write Cheque') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.cheques.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Cheque Details')" />

                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="bank_account_id" value="{{ __('Bank Account') }}" />
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
                                    <x-input-label for="date" value="{{ __('Date') }}" />
                                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                                </div>

                                <div>
                                    <x-input-label for="payee" value="{{ __('Payee') }}" />
                                    <x-text-input id="payee" name="payee" type="text" class="mt-1 block w-full" :value="old('payee')" placeholder="Who is this cheque payable to?" required />
                                </div>

                                <div>
                                    <x-input-label for="amount" value="{{ __('Amount') }}" />
                                    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                                </div>

                                <div>
                                    <x-input-label for="debit_account_id" value="{{ __('Expense/Asset Account') }}" />
                                    <p class="text-xs text-gray-500 mb-1">The account to debit (expense or asset).</p>
                                    <x-scoped-search-field
                                        name="debit_account_id"
                                        entity="account"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                        :value="old('debit_account_id')"
                                        :label="old('debit_account_id') ? (($expenseAccounts->firstWhere('id', (int) old('debit_account_id'))) ? $expenseAccounts->firstWhere('id', (int) old('debit_account_id'))->code . ' - ' . $expenseAccounts->firstWhere('id', (int) old('debit_account_id'))->name : '') : ''"
                                        placeholder="{{ __('Select Account') }}"
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('debit_account_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="memo" value="{{ __('Description') }}" />
                                    <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.cheques.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button type="submit">{{ __('Write Cheque') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
