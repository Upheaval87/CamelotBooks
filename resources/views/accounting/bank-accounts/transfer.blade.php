<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sticky-head">
                <div>
                    <h1>{{ __('Transfer Between Accounts') }}</h1>
                    <div class="sub">Move money from one bank account to another.</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.bank-accounts.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" form="transfer-form" class="btn btn-cta">{{ __('Transfer') }}</button>
                </div>
            </div>

            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6l6 6-6 6"/></svg></span>
                    <h2>{{ __('Transfer Details') }}</h2>
                    <span class="rule"></span>
                </div>

                <form method="POST" action="{{ route('accounting.bank-accounts.transfer') }}" id="transfer-form">
                    @csrf
                    <div class="g4">
                        <div class="field sp2">
                            <label for="from_account_id">{{ __('Transfer From') }} <span class="req">*</span></label>
                            <x-scoped-search-field
                                name="from_account_id"
                                entity="bank-account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                                :value="old('from_account_id')"
                                :label="old('from_account_id') ? ($bankAccounts->firstWhere('id', (int) old('from_account_id'))?->name ?? '') : ''"
                                placeholder="{{ __('Select source account') }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('from_account_id')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="to_account_id">{{ __('Transfer To') }} <span class="req">*</span></label>
                            <x-scoped-search-field
                                name="to_account_id"
                                entity="bank-account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                                :value="old('to_account_id')"
                                :label="old('to_account_id') ? ($bankAccounts->firstWhere('id', (int) old('to_account_id'))?->name ?? '') : ''"
                                placeholder="{{ __('Select destination account') }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('to_account_id')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="amount">{{ __('Amount') }} ({{ $cs }}) <span class="req">*</span></label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="input" value="{{ old('amount') }}" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="date">{{ __('Date') }} <span class="req">*</span></label>
                            <input id="date" name="date" type="date" class="input" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="description">{{ __('Description') }} <span class="req">*</span></label>
                            <input id="description" name="description" type="text" class="input" value="{{ old('description') }}" placeholder="{{ __('Transfer description') }}" required />
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
