<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <nav class="ac-crumbs">
                <a href="{{ route('accounting.cost-centers.index') }}">Cost Centres</a> <span>›</span> <span class="here">New</span>
            </nav>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.cost-centers.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
            </div>
        </div>

        <h1 class="ac-page-head-title">New Cost Centre</h1>

        <div class="ac-card">
            <div class="ac-pad">
                <form method="POST" action="{{ route('accounting.cost-centers.store') }}">
                    @csrf
                    <div class="ac-g2">
                        <div class="ac-f">
                            <label>Code</label>
                            <input class="in" name="code" value="{{ old('code') }}" placeholder="e.g. CC-001" required>
                        </div>
                        <div class="ac-f">
                            <label>Name</label>
                            <input class="in" name="name" value="{{ old('name') }}" placeholder="Cost Centre name" required>
                        </div>
                        <div class="ac-f">
                            <label>Department</label>
                            <input class="in" name="department" value="{{ old('department') }}" placeholder="e.g. Sales">
                        </div>
                        <div class="ac-f">
                            <label>Manager</label>
                            <input class="in" name="manager" value="{{ old('manager') }}" placeholder="Manager name">
                        </div>
                        <div class="ac-f">
                            <label>Parent Cost Centre</label>
                            <select class="in" name="parent_id">
                                <option value="">None (top-level)</option>
                                @foreach($parentCenters as $p)
                                <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ac-f">
                            <label>Description</label>
                            <input class="in" name="description" value="{{ old('description') }}" placeholder="Description">
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:20px">
                        <a href="{{ route('accounting.cost-centers.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
                        <button type="submit" class="ac-btn ac-btn-cta ac-btn-sm">Create Cost Centre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
