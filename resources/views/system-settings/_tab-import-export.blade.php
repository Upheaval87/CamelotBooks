<div class="settings-section-header">
    <div class="settings-section-eyebrow">11 · EXPORT SETTINGS</div>
    <div class="settings-section-title">Export Settings</div>
    <p class="settings-section-desc">Download all your company's system settings as a JSON file. This includes regional, currency, accounting, approval, numbering, and account mapping settings.</p>
    <hr class="settings-section-divider">
</div>

<div class="settings-card">
    <div class="settings-info-box mb-4">
        <strong>Included in export:</strong>
        <ul>
            <li>Regional Settings (language, timezone, date format)</li>
            <li>Currency Settings (base currency, decimal places, rate source)</li>
            <li>Accounting Settings (narration, credit limit, rounding, negative inventory)</li>
            <li>Default Account Mappings (33 mapping keys)</li>
            <li>Approval Settings (master toggle + per-document thresholds)</li>
            <li>Numbering Sequences (prefix, padding, reset policy for all 21 document types)</li>
        </ul>
    </div>
    <form method="POST" action="{{ route('system-settings.export-settings') }}">
        @csrf
        <button type="submit" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download Settings File
        </button>
    </form>
</div>

<div class="settings-section-header mt-8">
    <div class="settings-section-eyebrow">12 · IMPORT SETTINGS</div>
    <div class="settings-section-title">Import Settings</div>
    <p class="settings-section-desc">Upload a previously exported settings JSON file to apply its configuration to this company. Only compatible settings will be imported.</p>
    <hr class="settings-section-divider">
</div>

<div class="settings-card">
    <x-settings.callout variant="warn" class="mb-4">
        Importing settings will overwrite the current values for each setting present in the file. Account mappings will only be applied if the referenced account IDs exist in this company.
    </x-settings.callout>

    <form method="POST" action="{{ route('system-settings.import-settings') }}" enctype="multipart/form-data">
        @csrf
        <div class="flex items-end gap-4">
            <div class="flex-1">
                <label for="settings_file" class="settings-field-label">Settings File (JSON)</label>
                <input type="file" name="settings_file" id="settings_file" accept=".json,.txt" class="settings-field-file" />
            </div>
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Settings
            </button>
        </div>
    </form>
</div>
