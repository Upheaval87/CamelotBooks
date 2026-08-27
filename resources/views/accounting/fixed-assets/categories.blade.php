<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900 mb-6">Asset Categories</h1>
        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Description</th><th>Assets</th><th>Active</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($categories as $cat)
                        <tr>
                            <td>{{ $cat->code }}</td>
                            <td>{{ $cat->name }}</td>
                            <td>{{ $cat->description ?? '—' }}</td>
                            <td>{{ $cat->assets_count }}</td>
                            <td><span class="status-pill {{ $cat->is_active ? 'bg-mint-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('accounting.fixed-assets.categories.toggle', $cat->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gold-700 hover:underline text-sm">{{ $cat->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-8 text-slate-400">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
