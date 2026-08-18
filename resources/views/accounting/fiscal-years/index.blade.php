<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <h1>Fiscal Years</h1>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.fiscal-years.create') }}" class="ac-btn ac-btn-cta ac-btn-sm">New Fiscal Year</a>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-li-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th style="width:30%">Label</th>
                            <th style="width:15%">Start Date</th>
                            <th style="width:15%">End Date</th>
                            <th style="width:15%">Periods</th>
                            <th style="width:10%">Status</th>
                            <th style="width:10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fiscalYears as $fy)
                        @php
                            $statusLabel = $fy->isClosed() ? 'Closed' : ($fy->isOpen() ? 'Open' : 'Draft');
                            $statusClass = $fy->isOpen() ? 'ac-b-open' : 'ac-b-closed';
                        @endphp
                        <tr>
                            <td style="font-weight:700">{{ $fy->label }}</td>
                            <td class="ac-em">{{ $fy->start_date->format('d M Y') }}</td>
                            <td class="ac-em">{{ $fy->end_date->format('d M Y') }}</td>
                            <td class="ac-numr">{{ $fy->periods_count ?? $fy->periods->count() }}</td>
                            <td><span class="ac-badge {{ $statusClass }}"><span class="bdot"></span>{{ $statusLabel }}</span></td>
                            <td class="ac-row-act">
                                <a href="{{ route('accounting.fiscal-years.show', $fy) }}" class="ac-ibtn" title="View">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="ac-em" style="text-align:center;padding:40px">No fiscal years.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
