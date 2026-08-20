<x-app-layout>
    <div class="ac-wrap">
        <div class="page-head">
            <div>
                <h1>Cost Centres</h1>
                <div class="sub">Track costs by department, project or manager.</div>
            </div>
            <a href="{{ route('accounting.cost-centers.create') }}" class="ac-btn ac-btn-cta">&#43; New Cost Centre</a>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <span class="ac-ic">&#127970;</span>
                <h2>Cost Centres</h2>
                <div class="right">
                    @php
                        $total = $costCenters->count();
                        $active = $costCenters->where('is_active', true)->count();
                    @endphp
                    <span class="ac-tchip">{{ $total }} total &middot; {{ $active }} active</span>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table">
                    <thead class="ac-thead">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Manager</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="ac-tbody">
                        @forelse($costCenters as $cc)
                        <tr>
                            <td class="mono">{{ $cc->code }}</td>
                            <td class="name">{{ $cc->name }}</td>
                            <td class="ac-em">{{ $cc->department ?? '—' }}</td>
                            <td class="ac-em">{{ $cc->manager ?? '—' }}</td>
                            <td>
                                @if($cc->is_active)
                                <span class="ac-badge b-ok"><span class="bdot"></span>Active</span>
                                @else
                                <span class="ac-badge b-off"><span class="bdot"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="ac-row-act">
                                <a href="{{ route('accounting.cost-centers.show', $cc) }}" class="ac-ibtn" title="View">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="{{ route('accounting.cost-centers.create') }}?edit={{ $cc->id }}" class="ac-ibtn" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('accounting.cost-centers.toggle', $cc) }}" style="display:inline" onsubmit="return confirm('Toggle active status for {{ $cc->name }}?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="ac-ibtn" title="Toggle Status">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="ac-empty">
                                    <div class="ac-empty-ic">&#127970;</div>
                                    No cost centres.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
