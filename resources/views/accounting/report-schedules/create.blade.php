<x-app-layout>
    <x-slot name="header">New Report Schedule</x-slot>

    <div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="fr-toolbar">
            <h1 class="fr-title">New Report Schedule</h1>
        </div>

        <form action="{{ route('accounting.report-schedules.store') }}" method="POST" class="fr-card fr-form">
            @csrf

            <div class="fr-field">
                <label class="fr-label" for="report_key">Report</label>
                <select name="report_key" id="report_key" class="fr-input" required>
                    @foreach($reportOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('report_key') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label" for="frequency">Frequency</label>
                <select name="frequency" id="frequency" class="fr-input" required>
                    <option value="DAILY">Daily</option>
                    <option value="WEEKLY">Weekly</option>
                    <option value="MONTHLY">Monthly</option>
                </select>
                @error('frequency') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label" for="format">Output Format</label>
                <select name="format" id="format" class="fr-input" required>
                    <option value="PDF">PDF</option>
                    <option value="EXCEL">Excel (CSV)</option>
                </select>
                @error('format') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label" for="recipients">Recipients (comma-separated emails)</label>
                <input type="text" name="recipients" id="recipients" class="fr-input" required
                       placeholder="finance@company.com, cfo@company.com"
                       value="{{ old('recipients') }}">
                @error('recipients') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label" for="filters">Filters (JSON)</label>
                <textarea name="filters" id="filters" class="fr-input" rows="3"
                          placeholder='{"branch_id": null, "date_from": "2026-01-01", "date_to": "2026-12-31"}'>{{ old('filters', '{}') }}</textarea>
                <p class="fr-hint">Optional JSON filter set to apply when running this report.</p>
                @error('filters') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field fr-field--actions">
                <a href="{{ route('accounting.report-schedules.index') }}" class="fr-btn fr-btn--ghost">Cancel</a>
                <button type="submit" class="fr-btn fr-btn--primary">Create Schedule</button>
            </div>
        </form>
    </div>
</x-app-layout>
