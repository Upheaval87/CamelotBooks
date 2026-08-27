<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="fa-head mb-6">
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">Schedule Verification</h1>
        </div>

        <div class="card p-6">
            <form method="POST" action="{{ route('accounting.fixed-assets.verifications.store') }}">
                @csrf
                <div class="fa-detail-grid">
                    <div class="fa-field">
                        <label class="fa-label">Name</label>
                        <input type="text" name="name" required maxlength="255" class="input" placeholder="e.g. August Physical Count">
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Description</label>
                        <input type="text" name="description" maxlength="5000" class="input" placeholder="Optional">
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Scheduled Date</label>
                        <input type="date" name="scheduled_date" required class="input" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Branch</label>
                        <select name="branch_id" class="input">
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="margin-top:1.5rem;display:flex;gap:.75rem">
                    <button type="submit" class="fa-btn fa-btn-primary">Schedule</button>
                    <a href="{{ route('accounting.fixed-assets.verifications') }}" class="fa-btn fa-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
