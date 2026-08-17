<x-app-layout>
    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('New Transfer') }}</h1>
                    <p class="q2-sub">{{ __('Move money from one bank account to another.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.transfers') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Cancel') }}</a>
                </div>
            </div>

            <div class="q2-shell q2-shell--form">
                <div class="q2-main">
                    <form method="POST" action="{{ route('accounting.banking.transfers.store') }}" id="transfer-form">
                        @csrf

                        <div class="q2-sec">
                            <div class="q2-sec-head">
                                <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <div>
                                    <div class="q2-sec-title">{{ __('Transfer Details') }}</div>
                                    <div class="q2-sec-sub">{{ __('Both accounts are updated with a journal entry.') }}</div>
                                </div>
                            </div>
                            <div class="q2-sec-body">
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="from_account_id">{{ __('From Account') }} <span class="q2-req">*</span></label>
                                        <select id="from_account_id" name="from_account_id" class="q2-select" required>
                                            <option value="">— {{ __('Choose account') }} —</option>
                                            @foreach($bankAccounts as $acc)
                                                <option value="{{ $acc->id }}" @selected(old('from_account_id') == $acc->id)>{{ $acc->name }} · {{ $acc->code }}</option>
                                            @endforeach
                                        </select>
                                        @error('from_account_id')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="to_account_id">{{ __('To Account') }} <span class="q2-req">*</span></label>
                                        <select id="to_account_id" name="to_account_id" class="q2-select" required>
                                            <option value="">— {{ __('Choose account') }} —</option>
                                            @foreach($bankAccounts as $acc)
                                                <option value="{{ $acc->id }}" @selected(old('to_account_id') == $acc->id)>{{ $acc->name }} · {{ $acc->code }}</option>
                                            @endforeach
                                        </select>
                                        @error('to_account_id')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="amount">{{ __('Amount') }} <span class="q2-req">*</span></label>
                                        <input id="amount" type="number" step="0.01" min="0.01" name="amount" class="q2-input" required value="{{ old('amount') }}" placeholder="0.00" />
                                        @error('amount')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="date">{{ __('Date') }} <span class="q2-req">*</span></label>
                                        <input id="date" type="date" name="date" class="q2-input" required value="{{ old('date', now()->format('Y-m-d')) }}" />
                                        @error('date')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="q2-field">
                                    <label class="q2-label" for="description">{{ __('Description') }} <span class="q2-req">*</span></label>
                                    <input id="description" type="text" name="description" class="q2-input" required maxlength="500" value="{{ old('description') }}" placeholder="e.g. Monthly transfer to operations account" />
                                    @error('description')<span class="q2-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="q2-sec-actions">
                            <button type="submit" class="q2-btn q2-btn--cta">{{ __('Complete Transfer') }}</button>
                        </div>
                    </form>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.dashboard') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Banking Centre') }}</a>
                            <a href="{{ route('accounting.banking.transfers') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Transfers') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
