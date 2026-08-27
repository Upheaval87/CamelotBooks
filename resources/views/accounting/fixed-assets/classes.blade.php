<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="fa-head mb-6">
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">Asset Classes</h1>
        </div>

        <div class="card p-6 mb-6">
            <form method="POST" action="{{ route('accounting.fixed-assets.classes.store') }}" class="fa-detail-grid">
                @csrf
                <div class="fa-field">
                    <label class="fa-label">Code</label>
                    <input type="text" name="code" required maxlength="20" class="input" placeholder="e.g. BUILDINGS">
                </div>
                <div class="fa-field">
                    <label class="fa-label">Name</label>
                    <input type="text" name="name" required maxlength="255" class="input" placeholder="e.g. Buildings">
                </div>
                <div class="fa-field">
                    <label class="fa-label">Description</label>
                    <input type="text" name="description" maxlength="5000" class="input" placeholder="Optional description">
                </div>
                <div class="fa-field">
                    <label class="fa-label">Default Dep. Method</label>
                    <input type="text" name="default_dep_method" maxlength="50" class="input" placeholder="e.g. STRAIGHT_LINE">
                </div>
                <div class="fa-field">
                    <label class="fa-label">Default Useful Life (months)</label>
                    <input type="number" name="default_useful_life" min="1" max="600" class="input" placeholder="e.g. 120">
                </div>
                <div class="fa-field">
                    <label class="fa-label">Default Residual %</label>
                    <input type="number" name="default_residual_pct" min="0" max="100" step="0.01" class="input" placeholder="e.g. 10">
                </div>
                <div class="fa-field fa-field--action">
                    <button type="submit" class="fa-btn fa-btn--primary">Create Class</button>
                </div>
            </form>
        </div>

        <div class="fa-table-wrap">
            <table class="datasheet w-full">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Description</th><th>Default Method</th><th>Useful Life</th><th>Residual %</th><th>Assets</th><th>Active</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($classes as $cls)
                        <tr>
                            <td>{{ $cls->code }}</td>
                            <td>{{ $cls->name }}</td>
                            <td>{{ $cls->description ?? '—' }}</td>
                            <td>{{ $cls->default_dep_method ?? '—' }}</td>
                            <td>{{ $cls->default_useful_life ? $cls->default_useful_life . ' mo' : '—' }}</td>
                            <td>{{ $cls->default_residual_pct ? $cls->default_residual_pct . '%' : '—' }}</td>
                            <td>{{ $cls->assets_count }}</td>
                            <td><span class="status-pill {{ $cls->is_active ? 'bg-mint-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $cls->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('accounting.fixed-assets.classes.toggle', $cls->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gold-700 hover:underline text-sm">{{ $cls->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-8 text-slate-400">No classes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $classes->links() }}</div>
    </div>
</x-app-layout>
