<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'import-export'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="export-form" class="btn ghost">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ __('Download Settings File') }}
            </button>
            <button type="submit" form="import-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('Import Settings') }}
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-sec">
        <div class="sec-head">
            <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg></span>
            <h2>{{ __('Export Settings') }}</h2>
            <div class="rule"></div>
        </div>
        <p class="sub">Download all your company's system settings as a JSON file. This includes regional, currency, accounting, approval, numbering, and account mapping settings.</p>

        <form method="POST" action="{{ route('system-settings.export-settings') }}" id="export-form">
            @csrf
            <x-settings.callout variant="info" class="mb-4">
                <strong>Included in export:</strong>
                <ul>
                    <li>Regional Settings (language, timezone, date format)</li>
                    <li>Currency Settings (base currency, decimal places, rate source)</li>
                    <li>Accounting Settings (narration, credit limit, rounding, negative inventory)</li>
                    <li>Default Account Mappings (33 mapping keys)</li>
                    <li>Approval Settings (master toggle + per-document thresholds)</li>
                    <li>Numbering Sequences (prefix, padding, reset policy for all document types)</li>
                </ul>
            </x-settings.callout>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-sec">
        <div class="sec-head">
            <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg></span>
            <h2>{{ __('Import Settings') }}</h2>
            <div class="rule"></div>
        </div>
        <p class="sub">Upload a previously exported settings JSON file to apply its configuration to this company. Only compatible settings will be imported.</p>

        <form method="POST" action="{{ route('system-settings.import-settings') }}" enctype="multipart/form-data" id="import-form">
            @csrf
            <x-settings.callout variant="warn" class="mb-4">
                Importing settings will overwrite the current values for each setting present in the file. Account mappings will only be applied if the referenced account IDs exist in this company.
            </x-settings.callout>

            <div class="g2">
                <div class="field">
                    <label for="settings_file" class="label">Settings File (JSON)</label>
                    <input type="file" name="settings_file" id="settings_file" accept=".json,.txt" class="input" />
                </div>
            </div>
        </form>
    </div>
</div>
