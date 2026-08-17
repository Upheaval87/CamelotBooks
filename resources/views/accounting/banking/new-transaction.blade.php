<x-app-layout>
    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('New Bank Transaction') }}</h1>
                    <p class="q2-sub">{{ $bankAccount->name }} · {{ $bankAccount->code }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.register', $bankAccount->id) }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Cancel') }}</a>
                </div>
            </div>

            <div class="q2-shell q2-shell--form">
                <div class="q2-main">
                    <form method="POST" action="{{ route('accounting.banking.store-transaction', $bankAccount->id) }}" id="new-transaction-form">
                        @csrf
                        <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}" />

                        <div class="q2-sec">
                            <div class="q2-sec-head">
                                <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18M8 6v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <div>
                                    <div class="q2-sec-title">{{ __('Transaction Details') }}</div>
                                    <div class="q2-sec-sub">{{ __('Record a manual fee, withdrawal, deposit or interest.') }}</div>
                                </div>
                            </div>
                            <div class="q2-sec-body">
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="type">{{ __('Type') }} <span class="q2-req">*</span></label>
                                        <select id="type" name="type" class="q2-select" required>
                                            @foreach(['fee' => __('Bank Fee'), 'withdrawal' => __('Withdrawal'), 'deposit' => __('Deposit'), 'interest' => __('Interest')] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('type', 'withdrawal') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('type')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="date">{{ __('Date') }} <span class="q2-req">*</span></label>
                                        <input id="date" type="date" name="date" class="q2-input" required value="{{ old('date', now()->format('Y-m-d')) }}" />
                                        @error('date')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-field">
                                    <label class="q2-label" for="description">{{ __('Description') }} <span class="q2-req">*</span></label>
                                    <input id="description" type="text" name="description" class="q2-input" required maxlength="500" value="{{ old('description') }}" placeholder="e.g. Monthly bank charges" />
                                    @error('description')<span class="q2-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="amount">{{ __('Amount') }} <span class="q2-req">*</span></label>
                                        <input id="amount" type="number" step="0.01" min="0.01" name="amount" class="q2-input" required value="{{ old('amount') }}" placeholder="0.00" />
                                        @error('amount')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="reference">{{ __('Reference') }}</label>
                                        <input id="reference" type="text" name="reference" class="q2-input" maxlength="255" value="{{ old('reference') }}" placeholder="e.g. BANK-REF-001" />
                                        @error('reference')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="debit_account_id">{{ __('Offset Account (Debit)') }}</label>
                                        <select id="debit_account_id" name="debit_account_id" class="q2-select">
                                            <option value="">— {{ __('Choose account') }} —</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" @selected(old('debit_account_id') == $acc->id)>{{ $acc->code }} · {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('debit_account_id')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="credit_account_id">{{ __('Offset Account (Credit)') }}</label>
                                        <select id="credit_account_id" name="credit_account_id" class="q2-select">
                                            <option value="">— {{ __('Choose account') }} —</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" @selected(old('credit_account_id') == $acc->id)>{{ $acc->code }} · {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('credit_account_id')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <p class="q2-hint">{{ __('Bank fees and withdrawals post a debit to the offset account; deposits and interest post a credit.') }}</p>
                            </div>
                        </div>

                        <div class="q2-sec-actions">
                            <button type="submit" class="q2-btn q2-btn--cta">{{ __('Post Transaction') }}</button>
                        </div>
                    </form>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.register', $bankAccount->id) }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ $bankAccount->name }}</a>
                            <a href="{{ route('accounting.banking.accounts') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('Bank Accounts') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
