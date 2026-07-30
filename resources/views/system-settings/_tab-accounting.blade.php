<form method="POST" action="{{ route('system-settings.update-accounting') }}">
    @csrf
    @method('PUT')

    <div class="settings-section-header">
        <div class="settings-section-eyebrow">05 · ACCOUNTING SETTINGS</div>
        <div class="settings-section-title">Accounting Settings</div>
        <p class="settings-section-desc">Company-wide accounting controls and defaults.</p>
        <hr class="settings-section-divider">
    </div>

    <div class="space-y-4">
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

        <div class="settings-card">
            <div class="settings-field">
                <label for="rounding_tolerance" class="settings-field-label">
                    Rounding Tolerance ({{ \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$') }})
                </label>
                <input type="number" step="0.01" min="0" max="10" name="rounding_tolerance" id="rounding_tolerance"
                    value="{{ old('rounding_tolerance', $accounting['rounding_tolerance'] ?? '0.05') }}"
                    class="settings-field-input" style="max-width: 160px;" />
                <p class="settings-field-hint">Max amount a journal entry can be off due to rounding before being rejected. Default: 0.05</p>
            </div>
        </div>
    </div>

    <x-settings.callout variant="info" class="mt-4">
        Posting to closed accounting periods is always blocked by period locking — this is a hard rule and cannot be bypassed.
    </x-settings.callout>

    <div class="flex justify-end mt-4">
        <button type="submit" class="btn-primary">Save Accounting Settings</button>
    </div>
</form>
