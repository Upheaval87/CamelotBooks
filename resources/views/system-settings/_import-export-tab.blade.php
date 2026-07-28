<div class="space-y-6">
    {{-- Export Settings --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Export Settings</h3>
            <p class="mt-1 text-sm text-gray-600">Download all your company's system settings as a JSON file. This includes regional, currency, accounting, approval, numbering, and account mapping settings.</p>
        </div>
        <div class="p-6">
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Included in export:</h4>
                <ul class="text-xs text-gray-500 space-y-1">
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
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Settings File
                </button>
            </form>
        </div>
    </div>

    {{-- Import Settings --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Import Settings</h3>
            <p class="mt-1 text-sm text-gray-600">Upload a previously exported settings JSON file to apply its configuration to this company. Only compatible settings will be imported.</p>
        </div>
        <div class="p-6">
            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200 mb-4">
                <p class="text-sm text-yellow-800"><strong>Warning:</strong> Importing settings will overwrite the current values for each setting present in the file. Account mappings will only be applied if the referenced account IDs exist in this company.</p>
            </div>
            <form method="POST" action="{{ route('system-settings.import-settings') }}" enctype="multipart/form-data">
                @csrf
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <label for="settings_file" class="block text-sm font-medium text-gray-700">Settings File (JSON)</label>
                        <input type="file" name="settings_file" id="settings_file" accept=".json,.txt"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-800 file:text-white hover:file:bg-gray-700" />
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
