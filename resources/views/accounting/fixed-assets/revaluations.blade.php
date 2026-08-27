<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900 mb-6">Asset Revaluations</h1>
        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Asset</th><th>Date</th><th>Previous</th><th>New Value</th><th>Surplus</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($revaluations as $r)
                        <tr>
                            <td><a href="{{ route('accounting.fixed-assets.show', $r->asset_id) }}" class="text-gold-700 hover:underline">{{ $r->asset?->name }}</a></td>
                            <td>{{ $r->revaluation_date?->format('d M Y') }}</td>
                            <td>{{ format_number($r->previous_value) }}</td>
                            <td>{{ format_number($r->new_value) }}</td>
                            <td class="{{ $r->isUpward() ? 'text-green-700' : 'text-red-700' }}">{{ format_number($r->surplus_amount) }}</td>
                            <td><span class="status-pill">{{ $r->status_label }}</span></td>
                            <td>
                                @if ($r->isPending())
                                    <form method="POST" action="{{ route('accounting.fixed-assets.revaluations.approve', $r->id) }}" class="inline">@csrf <button class="text-green-700 hover:underline text-sm">Approve</button></form>
                                    <form method="POST" action="{{ route('accounting.fixed-assets.revaluations.reject', $r->id) }}" class="inline">@csrf <button class="text-red-600 hover:underline text-sm">Reject</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-slate-400">No revaluations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $revaluations->links() }}</div>
    </div>
</x-app-layout>
