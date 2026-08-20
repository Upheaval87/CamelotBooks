<x-app-layout>
    <div class="gl-suite">
        <div class="gl-wrap">
            <div class="gl-page-head">
                <div>
                    <h1>Fiscal Years</h1>
                    <div class="sub">Define fiscal years and their posting periods.</div>
                </div>
                <a href="{{ route('accounting.fiscal-years.create') }}" class="btn btn-cta">＋ New Fiscal Year</a>
            </div>

            <div class="gl-card">
                <div class="gl-card-h">
                    <span class="ic">🗓</span>
                    <h2>Fiscal Years</h2>
                    <div class="right"><span class="gl-chip">{{ $fiscalYears->count() }} {{ Str::plural('year', $fiscalYears->count()) }}</span></div>
                </div>
                <div class="gl-li-wrap">
                    <table class="gl-table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Periods</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fiscalYears as $fy)
                            @php
                                $isClosed = $fy->isClosed();
                                $periodCount = $fy->periods_count ?? $fy->periods->count();
                            @endphp
                            <tr>
                                <td class="gl-name">{{ $fy->label }}</td>
                                <td class="gl-em">{{ $fy->start_date->format('d M Y') }}</td>
                                <td class="gl-em">{{ $fy->end_date->format('d M Y') }}</td>
                                <td><span class="gl-chip{{ !$isClosed ? ' brand' : '' }}">{{ $periodCount }} {{ Str::plural('monthly', $periodCount) }}</span></td>
                                <td>
                                    @if($isClosed)
                                        <span class="gl-badge gl-b-off"><span class="bdot"></span>Closed</span>
                                    @else
                                        <span class="gl-badge gl-b-ok"><span class="bdot"></span>Active</span>
                                    @endif
                                </td>
                                <td class="gl-row-act">
                                    <a href="{{ route('accounting.fiscal-years.show', $fy) }}" class="gl-ibtn" title="View periods">📅</a>
                                    <a href="{{ route('accounting.fiscal-years.show', $fy) }}" class="gl-ibtn" title="Edit">✎</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="gl-empty">
                                    <div class="e">🗓</div>
                                    No fiscal years yet. Create your first to start tracking periods.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
