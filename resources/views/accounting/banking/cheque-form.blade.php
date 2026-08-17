<x-app-layout>
    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('Write Cheque') }}</h1>
                    <p class="q2-sub">{{ __('Issue a cheque from a bank account.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.cheques') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Cancel') }}</a>
                </div>
            </div>

            <div class="q2-shell q2-shell--form">
                <div class="q2-main">
                    <form method="POST" action="{{ route('accounting.banking.cheques.store') }}" id="cheque-form">
                        @csrf

                        <div class="q2-sec">
                            <div class="q2-sec-head">
                                <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 17V7m8 10V7M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <div>
                                    <div class="q2-sec-title">{{ __('Cheque Details') }}</div>
                                    <div class="q2-sec-sub">{{ __('A journal entry is posted and a cheque number is assigned automatically.') }}</div>
                                </div>
                            </div>
                            <div class="q2-sec-body">
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="bank_account_id">{{ __('Bank Account') }} <span class="q2-req">*</span></label>
                                        <select id="bank_account_id" name="bank_account_id" class="q2-select" required>
                                            <option value="">— {{ __('Choose account') }} —</option>
                                            @foreach($bankAccounts as $acc)
                                                <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>{{ $acc->name }} · {{ $acc->code }} (next {{ str_pad($acc->next_cheque_number ?? 1, 6, '0', STR_PAD_LEFT) }})</option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="date">{{ __('Date') }} <span class="q2-req">*</span></label>
                                        <input id="date" type="date" name="date" class="q2-input" required value="{{ old('date', now()->format('Y-m-d')) }}" />
                                        @error('date')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="payee">{{ __('Payee') }} <span class="q2-req">*</span></label>
                                        <input id="payee" type="text" name="payee" class="q2-input" required maxlength="255" value="{{ old('payee') }}" placeholder="e.g. ABC Supplies Ltd" />
                                        @error('payee')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="amount">{{ __('Amount') }} <span class="q2-req">*</span></label>
                                        <input id="amount" type="number" step="0.01" min="0.01" name="amount" class="q2-input" required value="{{ old('amount') }}" placeholder="0.00" />
                                        @error('amount')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-field">
                                    <label class="q2-label" for="debit_account_id">{{ __('Expense / Asset Account') }} <span class="q2-req">*</span></label>
                                    <select id="debit_account_id" name="debit_account_id" class="q2-select" required>
                                        <option value="">— {{ __('Choose account') }} —</option>
                                        @foreach($expenseAccounts as $acc)
                                            <option value="{{ $acc->id }}" @selected(old('debit_account_id') == $acc->id)>{{ $acc->code }} · {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('debit_account_id')<span class="q2-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="q2-field">
                                    <label class="q2-label" for="memo">{{ __('Memo') }}</label>
                                    <textarea id="memo" name="memo" class="q2-input" rows="2" maxlength="500">{{ old('memo') }}</textarea>
                                    @error('memo')<span class="q2-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="q2-sec-actions">
                            <button type="submit" class="q2-btn q2-btn--cta">{{ __('Write Cheque') }}</button>
                        </div>
                    </form>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.dashboard') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Banking Centre') }}</a>
                            <a href="{{ route('accounting.banking.cheques') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 17V7m8 10V7M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Cheques') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
