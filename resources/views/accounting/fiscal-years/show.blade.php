<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <nav class="ac-crumbs">
                <a href="{{ route('accounting.fiscal-years.index') }}">Fiscal Years</a> <span>›</span> <span class="here">{{ $fiscalYear->label }}</span>
            </nav>
            <div style="display:flex;gap:10px">
                @if($fiscalYear->isOpen())
                <form method="POST" action="{{ route('accounting.fiscal-years.close', $fiscalYear) }}" onsubmit="return confirm('Lock all periods in {{ $fiscalYear->label }}?')">
                    @csrf
                    <button type="submit" class="ac-btn ac-btn-ghost ac-btn-sm">Lock Fiscal Year</button>
                </form>
                @endif
                @if($fiscalYear->isClosed())
                <span class="ac-badge ac-b-closed"><span class="bdot"></span>Locked</span>
                @endif
            </div>
        </div>

        <div class="ac-card" style="margin-bottom:22px">
            <div class="ac-card-h">
                <h2>{{ $fiscalYear->label }}</h2>
                <div class="right">
                    <span class="ac-tchip">{{ $fiscalYear->start_date->format('d M Y') }} – {{ $fiscalYear->end_date->format('d M Y') }}</span>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table" style="min-width:auto">
                    <thead>
                        <tr>
                            <th style="width:30%">Period</th>
                            <th style="width:15%">Start Date</th>
                            <th style="width:15%">End Date</th>
                            <th style="width:15%">Status</th>
                            <th style="width:10%">Entries</th>
                            <th style="width:10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fiscalYear->periods as $period)
                        @php
                            $pStatusClass = $period->isLocked() ? 'ac-b-closed' : ($period->isOpen() ? 'ac-b-open' : 'ac-b-closed');
                            $pStatusLabel = $period->isLocked() ? 'Locked' : ($period->isOpen() ? 'Open' : 'Closed');
                        @endphp
                        <tr>
                            <td style="font-weight:600">{{ $period->label }}</td>
                            <td class="ac-em">{{ $period->start_date->format('d M Y') }}</td>
                            <td class="ac-em">{{ $period->end_date->format('d M Y') }}</td>
                            <td><span class="ac-badge {{ $pStatusClass }}"><span class="bdot"></span>{{ $pStatusLabel }}</span></td>
                            <td class="ac-numr">{{ $period->journal_entries_count ?? 0 }}</td>
                            <td class="ac-row-act">
                                @if($period->isOpen())
                                <form method="POST" action="{{ route('accounting.periods.lock', $period) }}" onsubmit="return confirm('Lock this period?')" style="display:inline">
                                    @csrf
                                    <button type="submit" class="ac-ibtn" title="Lock">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="ac-em" style="text-align:center;padding:24px">No periods generated.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
