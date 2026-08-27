<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <x-list-header title="Asset Register" description="View and manage all fixed assets" />
        @foreach ($stats as $label => $value)
            <span class="fa-chip">{{ ucfirst($label) }}: {{ $value }}</span>
        @endforeach
        <div class="fa-table-wrap mt-6">
            <table class="datasheet w-full">
                <thead>
                    <tr>
                        <th>Code</th><th>Name</th><th>Category</th><th>Status</th><th>Cost</th><th>NBV</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td><a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="text-gold-700 hover:underline">{{ $asset->asset_code }}</a></td>
                            <td>{{ $asset->name }}</td>
                            <td>{{ $asset->category?->name }}</td>
                            <td><span class="status-pill">{{ $asset->status_label }}</span></td>
                            <td>{{ format_number($asset->acquisition_cost) }}</td>
                            <td>{{ format_number($asset->net_book_value) }}</td>
                            <td><a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="text-gold-700 hover:underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-slate-400">No assets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $assets->links() }}</div>
    </div>
</x-app-layout>
