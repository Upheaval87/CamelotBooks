<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900 mb-6">Asset Disposals</h1>
        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Asset</th><th>Date</th><th>Method</th><th>NBV</th><th>Proceeds</th><th>Gain/Loss</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($disposals as $d)
                        <tr>
                            <td><a href="{{ route('accounting.fixed-assets.show', $d->asset_id) }}" class="text-gold-700 hover:underline">{{ $d->asset?->name }}</a></td>
                            <td>{{ $d->disposal_date?->format('d M Y') }}</td>
                            <td>{{ $d->method_label }}</td>
                            <td>{{ format_number($d->net_book_value) }}</td>
                            <td>{{ format_number($d->proceeds_amount) }}</td>
                            <td class="{{ $d->isGain() ? 'text-green-700' : 'text-red-700' }}">{{ format_number($d->gain_loss) }}</td>
                            <td><span class="status-pill">{{ $d->status_label }}</span></td>
                            <td>
                                @if ($d->isPending())
                                    <form method="POST" action="{{ route('accounting.fixed-assets.disposals.approve', $d->id) }}" class="inline">@csrf <button class="text-green-700 hover:underline text-sm">Approve</button></form>
                                    <form method="POST" action="{{ route('accounting.fixed-assets.disposals.reject', $d->id) }}" class="inline">@csrf <button class="text-red-600 hover:underline text-sm">Reject</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-8 text-slate-400">No disposals found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $disposals->links() }}</div>
    </div>
</x-app-layout>
