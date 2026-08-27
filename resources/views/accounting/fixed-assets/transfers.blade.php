<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900 mb-6">Asset Transfers</h1>
        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Asset</th><th>Date</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $t)
                        <tr>
                            <td><a href="{{ route('accounting.fixed-assets.show', $t->asset_id) }}" class="text-gold-700 hover:underline">{{ $t->asset?->name }}</a></td>
                            <td>{{ $t->transfer_date?->format('d M Y') }}</td>
                            <td>{{ $t->fromBranch?->name ?? $t->from_location ?? '—' }}</td>
                            <td>{{ $t->toBranch?->name ?? $t->to_location ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($t->reason, 40) }}</td>
                            <td><span class="status-pill">{{ $t->status_label }}</span></td>
                            <td>
                                @if ($t->isPending())
                                    <form method="POST" action="{{ route('accounting.fixed-assets.transfers.approve', $t->id) }}" class="inline">@csrf <button class="text-green-700 hover:underline text-sm">Approve</button></form>
                                    <form method="POST" action="{{ route('accounting.fixed-assets.transfers.reject', $t->id) }}" class="inline">@csrf <button class="text-red-600 hover:underline text-sm">Reject</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-slate-400">No transfers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $transfers->links() }}</div>
    </div>
</x-app-layout>
