<x-app-layout>
    @php
        $isEdit = $account !== null;
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ $isEdit ? __('Edit Bank Account') : __('Add Bank Account') }}</h1>
                    <p class="q2-sub">{{ $isEdit ? __('Update this bank account\'s details.') : __('Add a bank account to start recording transactions.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.accounts') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Cancel') }}</a>
                </div>
            </div>

            <div class="q2-shell q2-shell--form">
                <div class="q2-main">
                    <form method="POST" action="{{ $isEdit ? route('accounting.banking.accounts.update', $account->id) : route('accounting.banking.accounts.store') }}" id="bank-account-form">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

                        <div class="q2-sec">
                            <div class="q2-sec-head">
                                <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18M8 6v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <div>
                                    <div class="q2-sec-title">{{ __('Account Details') }}</div>
                                    <div class="q2-sec-sub">{{ __('Basic information about this bank account.') }}</div>
                                </div>
                            </div>
                            <div class="q2-sec-body">
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="code">{{ __('Account Code') }} <span class="q2-req">*</span></label>
                                        <input id="code" type="text" name="code" class="q2-input" required maxlength="20" value="{{ old('code', $account?->code) }}" placeholder="e.g. 1010" />
                                        @error('code')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="name">{{ __('Account Name') }} <span class="q2-req">*</span></label>
                                        <input id="name" type="text" name="name" class="q2-input" required maxlength="255" value="{{ old('name', $account?->name) }}" placeholder="e.g. Main Bank Account" />
                                        @error('name')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="currency">{{ __('Currency') }}</label>
                                        <select id="currency" name="currency" class="q2-select">
                                            <option value="">— {{ __('Default (') }}{{ $cs }}{{ __(')') }} —</option>
                                            @foreach($currencies as $cur)
                                                <option value="{{ $cur->code }}" @selected(old('currency', $account?->currency) === $cur->code)>{{ $cur->label() }}</option>
                                            @endforeach
                                        </select>
                                        @error('currency')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="next_cheque_number">{{ __('Next Cheque Number') }}</label>
                                        <input id="next_cheque_number" type="number" name="next_cheque_number" class="q2-input" min="1" value="{{ old('next_cheque_number', $account?->next_cheque_number ?? 1001) }}" />
                                        @error('next_cheque_number')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="opening_balance">{{ __('Opening Balance') }}</label>
                                        <input id="opening_balance" type="number" step="0.01" min="0" name="opening_balance" class="q2-input" value="{{ old('opening_balance', $account?->opening_balance ?? 0) }}" />
                                        @error('opening_balance')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="opening_balance_date">{{ __('Opening Balance Date') }}</label>
                                        <input id="opening_balance_date" type="date" name="opening_balance_date" class="q2-input" value="{{ old('opening_balance_date', $account?->opening_balance_date?->format('Y-m-d')) }}" />
                                        @error('opening_balance_date')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-field">
                                    <label class="q2-label" for="description">{{ __('Description') }}</label>
                                    <textarea id="description" name="description" class="q2-input" rows="2" maxlength="500">{{ old('description', $account?->description) }}</textarea>
                                    @error('description')<span class="q2-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="q2-sec-actions">
                            <button type="submit" class="q2-btn q2-btn--cta">{{ $isEdit ? __('Save Changes') : __('Create Bank Account') }}</button>
                        </div>
                    </form>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.dashboard') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Banking Centre') }}</a>
                            <a href="{{ route('accounting.banking.accounts') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>{{ __('Bank Accounts') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
