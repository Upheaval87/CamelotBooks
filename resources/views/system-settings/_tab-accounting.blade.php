<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'accounting'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="accounting-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('Save Accounting Settings') }}
            </button>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-accounting') }}" id="accounting-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                <h2>{{ __('Accounting Settings') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Company-wide accounting controls and defaults.</p>

            <x-settings.toggle
                name="mandatory_narration"
                label="Mandatory Narration on Journal Entries"
                description="Require a description/memo on every journal entry before posting."
                :checked="old('mandatory_narration', $accounting['mandatory_narration'] ?? '0') === '1'" />

            <x-settings.toggle
                name="enforce_credit_limit"
                label="Enforce Customer Credit Limits"
                description="Block new invoices when a customer exceeds their credit limit."
                :checked="old('enforce_credit_limit', $accounting['enforce_credit_limit'] ?? '0') === '1'" />

            <x-settings.toggle
                name="allow_negative_inventory"
                label="Allow Negative Inventory"
                description="Permit selling items when stock is at zero or below. Disabled by default."
                :checked="old('allow_negative_inventory', $accounting['allow_negative_inventory'] ?? ($company->allow_negative_stock ? '1' : '0')) === '1'" />

            <div class="g2" style="margin-top: 18px;">
                <div class="field">
                    <label for="rounding_tolerance" class="label">
                        Rounding Tolerance ({{ \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$') }})
                    </label>
                    <input type="number" step="0.01" min="0" max="10" name="rounding_tolerance" id="rounding_tolerance"
                        value="{{ old('rounding_tolerance', $accounting['rounding_tolerance'] ?? '0.05') }}"
                        class="input" style="max-width: 160px;" />
                    <p class="hint">Max amount a journal entry can be off due to rounding before being rejected. Default: 0.05</p>
                </div>
            </div>

            <div style="margin-top: 18px;">
                <x-settings.callout variant="info">
                    Posting to closed accounting periods is always blocked by period locking — this is a hard rule and cannot be bypassed.
                </x-settings.callout>
            </div>
        </div>
    </div>
</form>
