<form method="POST" action="{{ route('system-settings.update-currency') }}">
    @csrf
    @method('PUT')

    <div class="settings-section-header">
        <div class="settings-section-eyebrow">03 · CURRENCY SETTINGS</div>
        <div class="settings-section-title">Currency Settings</div>
        <p class="settings-section-desc">Configure the base currency and display preferences for monetary values.</p>
        <hr class="settings-section-divider">
    </div>

    <div class="settings-card">
        <div class="settings-grid">
            <x-settings.field label="Base Currency" name="base_currency" type="select" required hint="All journal entries balance in this currency. Foreign currency transactions are converted at the exchange rate.">
                @foreach([
                    'USD' => 'USD - US Dollar', 'EUR' => 'EUR - Euro', 'GBP' => 'GBP - British Pound',
                    'MWK' => 'MWK - Malawian Kwacha', 'KES' => 'KES - Kenyan Shilling',
                    'ZMW' => 'ZMW - Zambian Kwacha', 'ZWL' => 'ZWL - Zimbabwean Dollar',
                    'PHP' => 'PHP - Philippine Peso', 'JPY' => 'JPY - Japanese Yen',
                    'INR' => 'INR - Indian Rupee', 'ZAR' => 'ZAR - South African Rand',
                    'BWP' => 'BWP - Botswana Pula', 'TZS' => 'TZS - Tanzanian Shilling',
                    'UGX' => 'UGX - Ugandan Shilling', 'NGN' => 'NGN - Nigerian Naira',
                    'GHS' => 'GHS - Ghanaian Cedi', 'CAD' => 'CAD - Canadian Dollar',
                    'AUD' => 'AUD - Australian Dollar', 'CHF' => 'CHF - Swiss Franc',
                    'CNY' => 'CNY - Chinese Yuan',
                ] as $code => $label)
                    <option value="{{ $code }}" {{ old('base_currency', $company->base_currency) === $code ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
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

    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save Currency Settings</button>
    </div>
</form>
