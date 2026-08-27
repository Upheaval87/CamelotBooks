<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900 mb-6">Asset Impairments</h1>
        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Asset</th><th>Date</th><th>Carrying Value</th><th>Recoverable</th><th>Loss</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($impairments as $imp)
                        <tr>
                            <td><a href="{{ route('accounting.fixed-assets.show', $imp->asset_id) }}" class="text-gold-700 hover:underline">{{ $imp->asset?->name }}</a></td>
                            <td>{{ $imp->impairment_date?->format('d M Y') }}</td>
                            <td>{{ format_number($imp->carrying_value) }}</td>
                            <td>{{ format_number($imp->recoverable_amount) }}</td>
                            <td class="text-red-700">{{ format_number($imp->impairment_loss) }}</td>
                            <td><span class="status-pill">{{ $imp->status_label }}</span></td>
                            <td>
                                @if ($imp->isPending())
                                    <form method="POST" action="{{ route('accounting.fixed-assets.impairments.approve', $imp->id) }}" class="inline">@csrf <button class="text-green-700 hover:underline text-sm">Approve</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-slate-400">No impairments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $impairments->links() }}</div>
    </div>
</x-app-layout>
