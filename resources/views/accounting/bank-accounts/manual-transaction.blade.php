<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sticky-head">
                <div>
                    <h1>{{ __('Manual Bank Transaction') }} <span class="mono-chip">{{ $bankAccount->code }}</span></h1>
                    <div class="sub">{{ $bankAccount->name }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.bank-accounts.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" form="manual-tx-form" class="btn btn-cta">{{ __('Save Transaction') }}</button>
                </div>
            </div>

            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg></span>
                    <h2>{{ __('Transaction Details') }}</h2>
                    <span class="rule"></span>
                </div>

                <form method="POST" action="{{ route('accounting.bank-accounts.store-manual', $bankAccount->id) }}" id="manual-tx-form">
                    @csrf

                    <div class="g4">
                        <div class="field sp2">
                            <label>{{ __('Bank Account') }}</label>
                            <input type="text" class="input" value="{{ $bankAccount->name }}" disabled />
                            <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}" />
                        </div>
                        <div class="field sp2">
                            <label for="type">{{ __('Transaction Type') }} <span class="req">*</span></label>
                            <select id="type" name="type" class="input">
                                <option value="fee" {{ old('type', 'fee') === 'fee' ? 'selected' : '' }}>{{ __('Bank Fee') }}</option>
                                <option value="withdrawal" {{ old('type') === 'withdrawal' ? 'selected' : '' }}>{{ __('Withdrawal / Other Expense') }}</option>
                                <option value="deposit" {{ old('type') === 'deposit' ? 'selected' : '' }}>{{ __('Deposit / Other Income') }}</option>
                                <option value="interest" {{ old('type') === 'interest' ? 'selected' : '' }}>{{ __('Interest Earned') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="date">{{ __('Date') }} <span class="req">*</span></label>
                            <input id="date" name="date" type="date" class="input" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="amount">{{ __('Amount') }} ({{ $cs }}) <span class="req">*</span></label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="input" value="{{ old('amount') }}" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div class="field sp2" x-data="{ txType: '{{ old('type', 'fee') }}' }">
                            <div x-show="txType === 'fee' || txType === 'withdrawal'">
                                <label for="debit_account_id">{{ __('Expense Account (Debit)') }} <span class="req">*</span></label>
                                <p class="hint">The expense/asset account to charge this transaction to.</p>
                                <x-scoped-search-field
                                    name="debit_account_id"
                                    entity="account"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                    :value="old('debit_account_id')"
                                    :label="old('debit_account_id') ? (($accounts->firstWhere('id', (int) old('debit_account_id'))) ? $accounts->firstWhere('id', (int) old('debit_account_id'))->code . ' - ' . $accounts->firstWhere('id', (int) old('debit_account_id'))->name : '') : ''"
                                    placeholder="{{ __('Select account') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('debit_account_id')" class="mt-2" />
                            </div>
                            <div x-show="txType === 'deposit' || txType === 'interest'">
                                <label for="credit_account_id">{{ __('Income/Credit Account') }} <span class="req">*</span></label>
                                <p class="hint">The income/equity account to credit for this deposit.</p>
                                <x-scoped-search-field
                                    name="credit_account_id"
                                    entity="account"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                    :value="old('credit_account_id')"
                                    :label="old('credit_account_id') ? (($accounts->firstWhere('id', (int) old('credit_account_id'))) ? $accounts->firstWhere('id', (int) old('credit_account_id'))->code . ' - ' . $accounts->firstWhere('id', (int) old('credit_account_id'))->name : '') : ''"
                                    placeholder="{{ __('Select account') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('credit_account_id')" class="mt-2" />
                            </div>
                        </div>

                        <div class="field sp2">
                            <label for="description">{{ __('Description') }} <span class="req">*</span></label>
                            <input id="description" name="description" type="text" class="input" value="{{ old('description') }}" placeholder="{{ __('Transaction description') }}" required />
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="reference">{{ __('Reference') }}</label>
                            <input id="reference" name="reference" type="text" class="input" value="{{ old('reference') }}" placeholder="{{ __('Optional reference') }}" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
