<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <h1>Cost Centres</h1>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.cost-centers.create') }}" class="ac-btn ac-btn-cta ac-btn-sm">New Cost Centre</a>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-li-wrap">
                <table class="ac-table" style="min-width:auto">
                    <thead>
                        <tr>
                            <th style="width:15%">Code</th>
                            <th style="width:25%">Name</th>
                            <th style="width:15%">Department</th>
                            <th style="width:15%">Manager</th>
                            <th style="width:10%">Status</th>
                            <th style="width:10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($costCenters as $cc)
                        <tr>
                            <td class="ac-mono">{{ $cc->code }}</td>
                            <td style="font-weight:600">{{ $cc->name }}</td>
                            <td class="ac-em">{{ $cc->department ?? '—' }}</td>
                            <td class="ac-em">{{ $cc->manager ?? '—' }}</td>
                            <td>
                                @if($cc->is_active)
                                <span class="ac-badge ac-b-open"><span class="bdot"></span>Active</span>
                                @else
                                <span class="ac-badge ac-b-closed"><span class="bdot"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="ac-row-act">
                                <a href="{{ route('accounting.cost-centers.show', $cc) }}" class="ac-ibtn" title="View">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="ac-em" style="text-align:center;padding:40px">No cost centres.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
