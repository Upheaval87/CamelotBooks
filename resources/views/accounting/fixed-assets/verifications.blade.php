<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="fa-head mb-6">
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">Asset Verifications</h1>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.verifications.create') }}" class="fa-btn fa-btn-primary">Schedule Verification</a>
            </div>
        </div>

        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Name</th><th>Branch</th><th>Scheduled</th><th>Progress</th><th>Assigned To</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($verifications as $v)
                        <tr>
                            <td>{{ $v->name }}</td>
                            <td>{{ $v->branch?->name ?? 'All Branches' }}</td>
                            <td>{{ $v->scheduled_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="numr">{{ $v->verified_count }}/{{ $v->total_assets }}</span>
                                <span style="color:var(--muted,#5f7476);font-size:.75rem"> ({{ number_format($v->completionPercentage(), 1) }}%)</span>
                            </td>
                            <td>{{ $v->assignee?->name ?? '—' }}</td>
                            <td><span class="fa-chip {{ $v->status === 'completed' ? 'fa-chip-teal' : ($v->status === 'cancelled' ? 'fa-chip-red' : 'fa-chip-amber') }}">{{ $v->status_label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-8 text-slate-400">No verifications scheduled.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $verifications->links() }}</div>
    </div>
</x-app-layout>
