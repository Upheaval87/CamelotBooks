<x-app-layout>
    <x-slot name="header">Edit Report Schedule</x-slot>

    <div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="fr-toolbar">
            <h1 class="fr-title">Edit Report Schedule</h1>
        </div>

        <form action="{{ route('accounting.report-schedules.update', $schedule->id) }}" method="POST" class="fr-card fr-form">
            @csrf
            @method('PUT')

            <div class="fr-field">
                <label class="fr-label">Report</label>
                <p class="fr-input-readonly">{{ $reportOptions[$schedule->report_key] ?? $schedule->report_key }}</p>
            </div>

            <div class="fr-field">
                <label class="fr-label" for="frequency">Frequency</label>
                <select name="frequency" id="frequency" class="fr-input" required>
                    <option value="DAILY" {{ $schedule->frequency === 'DAILY' ? 'selected' : '' }}>Daily</option>
                    <option value="WEEKLY" {{ $schedule->frequency === 'WEEKLY' ? 'selected' : '' }}>Weekly</option>
                    <option value="MONTHLY" {{ $schedule->frequency === 'MONTHLY' ? 'selected' : '' }}>Monthly</option>
                </select>
                @error('frequency') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label" for="format">Output Format</label>
                <select name="format" id="format" class="fr-input" required>
                    <option value="PDF" {{ $schedule->format === 'PDF' ? 'selected' : '' }}>PDF</option>
                    <option value="EXCEL" {{ $schedule->format === 'EXCEL' ? 'selected' : '' }}>Excel (CSV)</option>
                </select>
                @error('format') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label" for="recipients">Recipients (comma-separated emails)</label>
                <input type="text" name="recipients" id="recipients" class="fr-input" required
                       value="{{ $recipientsStr }}">
                @error('recipients') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label" for="filters">Filters (JSON)</label>
                <textarea name="filters" id="filters" class="fr-input" rows="3">{{ $filtersJson }}</textarea>
                @error('filters') <span class="fr-error">{{ $message }}</span> @enderror
            </div>

            <div class="fr-field">
                <label class="fr-label">Last Run</label>
                <p class="fr-input-readonly">
                    @if($schedule->last_run_at)
                        {{ $schedule->last_run_at->format('M d, Y H:i') }} —
                        <span class="fr-badge {{ $schedule->last_run_status === 'SUCCESS' ? 'fr-badge--active' : 'fr-badge--failed' }}">
                            {{ $schedule->last_run_status }}
                        </span>
                        @if($schedule->last_error)
                            <br><span class="text-sm text-red-600">{{ $schedule->last_error }}</span>
                        @endif
                    @else
                        Never run
                    @endif
                </p>
            </div>

            <div class="fr-field fr-field--actions">
                <a href="{{ route('accounting.report-schedules.index') }}" class="fr-btn fr-btn--ghost">Cancel</a>
                <button type="submit" class="fr-btn fr-btn--primary">Save Changes</button>
            </div>
        </form>
    </div>
</x-app-layout>
