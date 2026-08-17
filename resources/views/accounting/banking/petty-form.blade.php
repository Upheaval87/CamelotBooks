<x-app-layout>
    <div class="q2 py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="q2-head">
                <div>
                    <h1 class="q2-title">{{ __('New Petty Cash Fund') }}</h1>
                    <p class="q2-sub">{{ __('Create a petty cash fund to track small cash expenses.') }}</p>
                </div>
                <div class="q2-head-actions">
                    <a href="{{ route('accounting.banking.petty') }}" class="q2-btn q2-btn--ghost q2-btn--sm">{{ __('Cancel') }}</a>
                </div>
            </div>

            <div class="q2-shell q2-shell--form">
                <div class="q2-main">
                    <form method="POST" action="{{ route('accounting.banking.petty.store') }}" id="petty-form">
                        @csrf

                        <div class="q2-sec">
                            <div class="q2-sec-head">
                                <span class="q2-sec-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <div>
                                    <div class="q2-sec-title">{{ __('Fund Details') }}</div>
                                    <div class="q2-sec-sub">{{ __('The fund starts empty — establish it from a bank account afterwards.') }}</div>
                                </div>
                            </div>
                            <div class="q2-sec-body">
                                <div class="q2-g2">
                                    <div class="q2-field">
                                        <label class="q2-label" for="name">{{ __('Fund Name') }} <span class="q2-req">*</span></label>
                                        <input id="name" type="text" name="name" class="q2-input" required maxlength="255" value="{{ old('name') }}" placeholder="e.g. Office Petty Cash" />
                                        @error('name')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="q2-field">
                                        <label class="q2-label" for="code">{{ __('Account Code') }} <span class="q2-req">*</span></label>
                                        <input id="code" type="text" name="code" class="q2-input" required maxlength="20" value="{{ old('code') }}" placeholder="e.g. 1030" />
                                        @error('code')<span class="q2-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="q2-sec-actions">
                            <button type="submit" class="q2-btn q2-btn--cta">{{ __('Create Fund') }}</button>
                        </div>
                    </form>
                </div>

                <div class="q2-rail">
                    <div class="q2-railcard">
                        <div class="q2-rail-group">
                            <div class="q2-rail-label">{{ __('Banking') }}</div>
                            <a href="{{ route('accounting.banking.dashboard') }}" class="q2-vitem"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 4l9 6.5M5 9v11h14V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Banking Centre') }}</a>
                            <a href="{{ route('accounting.banking.petty') }}" class="q2-vitem is-active"><span class="q2-vitem-ic"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v12m-3-2a3 3 0 003 3h1a2 2 0 002-2v-1a2 2 0 00-2-2H9a2 2 0 01-2-2V9a2 2 0 012-2h1a3 3 0 013 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>{{ __('Petty Cash') }}</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
