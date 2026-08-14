<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('New Bank Reconciliation') }}</h1>
                    <div class="sub">Create a reconciliation period for a bank account, then import your statement.</div>
                </div>
                <div class="br-cluster">
                    <a href="{{ route('accounting.bank-reconciliation.index') }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('Back') }}
                    </a>
                </div>
            </div>

            @if($errors->any())
                <div class="note-info" style="margin-bottom:16px">
                    <strong>{{ __('Unable to create the reconciliation') }}:</strong>
                    <ul style="margin:6px 0 0 18px;list-style:disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card" style="max-width:1120px;margin:0 auto">
                <div class="card-h">
                    <h2>{{ __('New Bank Reconciliation') }}</h2>
                    <span class="badge b-gray"><span class="bdot"></span>{{ __('Draft') }}</span>
                </div>
                <div class="card-b">
                    <div class="stepper">
                        <div class="stp"><span class="d cur">1</span><span class="t cur">{{ __('Statement') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d todo">2</span><span class="t">{{ __('Import') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d todo">3</span><span class="t">{{ __('Matching') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d todo">4</span><span class="t">{{ __('Review') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d todo">5</span><span class="t">{{ __('Complete') }}</span></div>
                    </div>

                    <form method="POST" action="{{ route('accounting.bank-reconciliation.store') }}">
                        @csrf
                        <div class="g3">
                            <div class="field">
                                <label for="bank_account_id">{{ __('Bank Account') }} <span class="req">*</span></label>
                                <select id="bank_account_id" name="bank_account_id" class="input" required>
                                    <option value="">Select a bank account…</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}" @selected((int) old('bank_account_id', $preselectedBankAccountId) === (int) $account->id)>
                                            {{ $account->code }} — {{ $account->name }}
                                            @if($account->currency) ({{ $account->currency }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')<div class="err">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="statement_date">{{ __('Statement Date') }} <span class="req">*</span></label>
                                <input id="statement_date" name="statement_date" type="date" class="input" value="{{ old('statement_date', now()->format('Y-m-d')) }}" required />
                                @error('statement_date')<div class="err">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="statement_number">{{ __('Statement Number') }}</label>
                                <input id="statement_number" name="statement_number" type="text" class="input" value="{{ old('statement_number') }}" placeholder="Optional" />
                                @error('statement_number')<div class="err">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="opening_balance">{{ __('Opening Balance') }} ({{ $cs }}) <span class="req">*</span></label>
                                <input id="opening_balance" name="opening_balance" type="number" step="0.01" class="input" value="{{ old('opening_balance', $defaultOpeningBalance ?? '') }}" required />
                                @error('opening_balance')<div class="err">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="closing_balance">{{ __('Closing Balance') }} ({{ $cs }}) <span class="req">*</span></label>
                                <input id="closing_balance" name="closing_balance" type="number" step="0.01" class="input" value="{{ old('closing_balance') }}" required />
                                @error('closing_balance')<div class="err">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="currency">{{ __('Currency') }} <span class="req">*</span></label>
                                <select id="currency" name="currency" class="input" required>
                                    @forelse($currencies as $cur)
                                        <option value="{{ $cur->code }}" @selected(old('currency', $systemCurrency) === $cur->code)>{{ $cur->code }} — {{ $cur->name }}</option>
                                    @empty
                                        <option value="{{ $systemCurrency }}" selected>{{ $systemCurrency }}</option>
                                    @endforelse
                                </select>
                                <div class="hint">{{ __('Defaults to your system currency — set in Settings → Currency.') }}</div>
                                @error('currency')<div class="err">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        @if($approvalSetting && (bool) $approvalSetting->requires_approval)
                            <div class="note-info" style="margin-top:16px">
                                Approval is required for this company. Reconciliations must be approved before they can be completed.
                            </div>
                        @endif

                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                            <a href="{{ route('accounting.bank-reconciliation.index') }}" class="btn ghost">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn cta">{{ __('Save & Continue') }} &rarr;</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
