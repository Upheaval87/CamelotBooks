<x-app-layout>
    <x-slot name="header">Report Schedules</x-slot>

    <div class="max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="fr-toolbar">
            <h1 class="fr-title">Scheduled Reports</h1>
            <a href="{{ route('accounting.report-schedules.create') }}" class="fr-btn fr-btn--primary">New Schedule</a>
        </div>

        @if(session('success'))
            <div class="fr-alert fr-alert--success">{{ session('success') }}</div>
        @endif

        @if($schedules->isEmpty())
            <div class="fr-card">
                <p class="fr-empty">No report schedules configured. Click "New Schedule" to set up automated report delivery.</p>
            </div>
        @else
            <div class="fr-table-wrap">
                <table class="fr-table">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Frequency</th>
                            <th>Format</th>
                            <th>Recipients</th>
                            <th>Status</th>
                            <th>Last Run</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $schedule)
                            <tr>
                                <td class="font-medium text-gray-900">{{ $reportOptions[$schedule->report_key] ?? $schedule->report_key }}</td>
                                <td>{{ $schedule->frequency }}</td>
                                <td>{{ $schedule->format }}</td>
                                <td class="text-sm text-gray-600">{{ implode(', ', $schedule->recipients ?? []) }}</td>
                                <td>
                                    @if($schedule->active)
                                        <span class="fr-badge fr-badge--active">Active</span>
                                    @else
                                        <span class="fr-badge fr-badge--inactive">Paused</span>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-600">
                                    @if($schedule->last_run_at)
                                        {{ $schedule->last_run_at->format('M d, Y H:i') }}
                                        @if($schedule->last_run_status === 'FAILED')
                                            <span class="fr-badge fr-badge--failed">Failed</span>
                                        @endif
                                    @else
                                        Never
                                    @endif
                                </td>
                                <td class="fr-actions">
                                    <form action="{{ route('accounting.report-schedules.toggle', $schedule->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="fr-btn fr-btn--ghost fr-btn--sm">
                                            {{ $schedule->active ? 'Pause' : 'Activate' }}
                                        </button>
                                    </form>
                                    <a href="{{ route('accounting.report-schedules.edit', $schedule->id) }}" class="fr-btn fr-btn--ghost fr-btn--sm">Edit</a>
                                    <form action="{{ route('accounting.report-schedules.destroy', $schedule->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this schedule?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="fr-btn fr-btn--danger fr-btn--sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $schedules->links() }}</div>
        @endif
    </div>
</x-app-layout>
