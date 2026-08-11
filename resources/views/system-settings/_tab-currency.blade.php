<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'currency'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="currency-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('Save Currency Settings') }}
            </button>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-currency') }}" id="currency-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <h2>{{ __('Currency Settings') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Configure the base currency and display preferences for monetary values.</p>

            <div class="g3">
                <x-settings.field label="Base Currency" name="base_currency" type="select" required hint="All journal entries balance in this currency. Foreign currency transactions are converted at the exchange rate.">
                    @forelse($currencies as $curOption)
                        <option value="{{ $curOption->code }}" {{ old('base_currency', $company->base_currency) === $curOption->code ? 'selected' : '' }}>{{ $curOption->label() }}</option>
                    @empty
                        <option value="{{ $company->base_currency }}" selected>{{ $company->base_currency }}</option>
                    @endforelse
                </x-settings.field>
                <x-settings.field label="Decimal Places for Display" name="decimal_places" type="select" required hint="Number of decimal places for display purposes. Journals always use full precision.">
                    @foreach([0, 2, 3, 4] as $dp)
                        <option value="{{ $dp }}" {{ old('decimal_places', $currency['decimal_places'] ?? '2') == $dp ? 'selected' : '' }}>
                            {{ $dp }}{{ $dp === 0 ? ' (whole numbers)' : ($dp === 2 ? ' (standard)' : '') }}
                        </option>
                    @endforeach
                </x-settings.field>
                <x-settings.field label="Exchange Rate Source" name="rate_source" type="select" required hint="Currently, exchange rates are entered manually via the Exchange Rates screen. Live rate feeds are not yet available.">
                    <option value="manual" {{ old('rate_source', $currency['rate_source'] ?? 'manual') === 'manual' ? 'selected' : '' }}>Manual Entry Only</option>
                </x-settings.field>
                <x-settings.field label="Currency Symbol" name="currency_symbol" type="text" :value="old('currency_symbol', $currency['currency_symbol'] ?? '$')" placeholder="e.g. K, $, MWK, EUR" hint="The symbol or code shown before amounts (e.g. K, $, MWK)." />
            </div>
        </div>
    </div>
</form>
