<x-app-layout>
    <div class="fa-wrap">
        <div class="fa-head">
            <div><h1>Depreciation Runs</h1></div>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.depreciation-runs.create') }}" class="fa-btn fa-btn-primary">New Run</a>
            </div>
        </div>
        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Run #</th><th>Period</th><th>Book</th><th>Assets</th><th>Total Dep.</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td>{{ $run->run_number }}</td>
                            <td>{{ $run->period }}</td>
                            <td>{{ ucfirst($run->book_type) }}</td>
                            <td>{{ $run->asset_count }}</td>
                            <td>{{ format_number($run->total_depreciation) }}</td>
                            <td><span class="status-pill">{{ $run->status_label }}</span></td>
                            <td>
                                @if ($run->isDraft())
                                    <form method="POST" action="{{ route('accounting.fixed-assets.depreciation-runs.post', $run->id) }}" class="inline">@csrf <button class="text-gold-700 hover:underline text-sm">Post</button></form>
                                @endif
                                @if ($run->isPosted())
                                    <form method="POST" action="{{ route('accounting.fixed-assets.depreciation-runs.reverse', $run->id) }}" class="inline">@csrf <button class="text-red-600 hover:underline text-sm">Reverse</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-slate-400">No depreciation runs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $runs->links() }}</div>
    </div>
</x-app-layout>
